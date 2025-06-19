<?php
/**
 * @brief Related Entries, a plugin for Dotclear 2
 *
 * @package    Dotclear
 * @subpackage Plugins
 *
 * @author Philippe aka amalgame
 *
 * @copyright GPL-2.0 [https://www.gnu.org/licenses/gpl-2.0.html]
 */

declare(strict_types=1);

namespace Dotclear\Plugin\relatedEntries;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Core\Backend\Utility;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Network\Http;
use Exception;

class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs) {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,
                    ]),
                ]);
            },
        ]);

        My::addBackendMenuItem(Utility::MENU_BLOG);

        if ((isset($_GET['addlinks']) && $_GET['addlinks'] == 1) || (isset($_GET['p']) && $_GET['p'] == 'relatedEntries')) {
            App::behavior()->addBehavior('adminColumnsListsV2', [BackendBehaviors::class, 'adminColumnsLists']);
            App::behavior()->addBehavior('adminPostListHeaderV2', [BackendBehaviors::class, 'adminPostListHeader']);
            App::behavior()->addBehavior('adminPostListValueV2', [BackendBehaviors::class, 'adminPostListValue']);
        }

        App::behavior()->addBehavior('adminPageHelpBlock', [self::class,  'adminPageHelpBlock']);
        App::behavior()->addBehavior('adminPostHeaders', [self::class,  'postHeaders']);
        App::behavior()->addBehavior('adminPostForm', [self::class,  'adminPostForm']);
        App::behavior()->addBehavior('initWidgets', [Widgets::class, 'initWidgets']);

        if (isset($_GET['id']) && isset($_GET['r_id'])) {
            try {
                $meta  = App::meta();
                $id    = $_GET['id'];
                $r_ids = $_GET['r_id'];

                foreach ($meta->splitMetaValues($r_ids) as $tag) {
                    $meta->delPostMeta($id, 'relatedEntries', $tag);
                    $meta->delPostMeta($tag, 'relatedEntries', $id);
                }

                Http::redirect(App::postTypes()->get('post')->adminUrl($id, false, ['del' => 1,'upd' => 1]));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function adminPageHelpBlock(ArrayObject $blocks): void
    {
        if (array_search('core_post', $blocks->getArrayCopy(), true) !== false) {
            $blocks->append('post');
        }
    }

    public static function postHeaders(): string
    {
        if (!My::settings()->relatedEntries_enabled) {
            return '';
        }

        if (isset($_GET['p'])) {
            return '';
        }

        return
        '<script>' . "\n" .
        '$(document).ready(function() {' . "\n" .
            '$(\'#relatedEntries-area label\').toggleWithLegend($(\'#relatedEntries-list\'), {' . "\n" .
                'legend_click: true,' . "\n" .
                'user_pref: \'dcx_relatedEntries_detail\'' . "\n" .

            '});' . "\n" .
            '$(\'a.link-remove\').click(function() {' . "\n" .
            'msg = \'' . __('Are you sure you want to remove this link to a related post?') . '\';' . "\n" .
            'if (!window.confirm(msg)) {' . "\n" .
                'return false;' . "\n" .
            '}' . "\n" .
            '});' . "\n" .
            '$(\'a.links-remove\').click(function() {' . "\n" .
            'msg = \'' . __('Are you sure you want to remove all links to related posts?') . '\';' . "\n" .
            'if (!window.confirm(msg)) {' . "\n" .
                'return false;' . "\n" .
            '}' . "\n" .
            '});' . "\n" .
        '});' . "\n" .
        '</script>' .
        My::cssLoad('admin-post.css') . "\n";
    }

    public static function adminPostForm($post)
    {
        $postTypes = ['post'];

        if (!My::settings()->relatedEntries_enabled) {
            return;
        }
        if (is_null($post) || !in_array($post->post_type, $postTypes)) {
            return;
        }

        $id      = $post->post_id;
        $post_type    = $post->post_type;
        $meta    = App::meta();
        $meta_rs = $meta->getMetaStr($post->post_meta, 'relatedEntries');

        $addlinksurl    = App::backend()->url()->get('admin.plugin', ['p' => My::id(),'id' => $id, 'upd' => '1','addlinks' => '1'], '&');
        $removelinksurl = App::backend()->url()->get('admin.plugin', ['p' => My::id(),'id' => $id, 'upd' => '1','r_id' => $meta_rs], '&');

        $form_note = (new Span(__('Links to related posts.')))->class('form-note')->render();

        $addlinks_message = (new Para())
        ->items([
            (new Link())
                ->class('add')
                ->href($addlinksurl)
                ->items([
                    ((new Strong(__('Add links')))),
                ]),
        ]);

        if (!$meta_rs) {
            echo
            (new Div())->class('area')->id('relatedEntries-area')->items([
                (new Label(__('Related entries:') . ' ' . $form_note))
                    ->class('bold')
                    ->for('relatedEntries-list'),
                (new Div())->id('relatedEntries-list')->items([
                    (new Para())
                        ->items([
                            (new Text('span', __('No related posts')))
                            ->class(['form-note', 'info', 'maximal']),
                        ])
                        ->class('elements-list'),
                    $addlinks_message,
                ]),
            ])->render();
        } else {
            // Get related posts
            try {
                App::blog()->withoutPassword(false);

                $params['post_id']    = $meta->splitMetaValues($meta_rs);
                $params['no_content'] = true;
                $params['post_type']  = ['post'];

                $post_type = 'post';

                $posts     = App::blog()->getPosts($params);
                $counter   = App::blog()->getPosts($params, true);
                $post_list = new BackendMiniList($posts, $counter->f(0));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
            App::backend()->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            App::backend()->nb_per_page = UserPref::getUserFilters('pages', 'nb');

            echo
            (new Div())->class('area')->id('relatedEntries-area')->items([
                (new Label(__('Related entries:') . ' ' . $form_note))
                    ->class('bold')
                    ->for('relatedEntries-list'),
                (new Div())->id('relatedEntries-list')->items([
                    (new Div())->items([
                        (new Capture($post_list->display(...), [App::backend()->page, App::backend()->nb_per_page, (int) $id, $enclose_block = '', (string) $post_type])),
                        (new Ul())
                        ->class('minilist')
                        ->items([
                            (new Li())->items([
                                (new Link())
                                    ->class('add')
                                    ->href($addlinksurl)
                                    ->items([
                                        ((new Strong(__('Add links')))),
                                    ]),
                            ]),
                            (new Li())->class('right')->items([
                                (new Link())->href($removelinksurl)
                                    ->class(['links-remove', 'delete'])
                                    ->items([
                                        ((new Strong(__('Remove all links')))),
                                    ]),
                            ]),
                        ]),
                    ]),

                ]),
            ])->render();
        }
    }
}
