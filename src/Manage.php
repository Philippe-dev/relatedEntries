<?php
/**
 * @brief Related Entries, a plugin for Dotclear 2
 *
 * @package    Dotclear
 * @subpackage Plugins
 *
 * @author Philippe aka amalgame
 *
 * @copyright AGPL-3.0
 */

declare(strict_types=1);

namespace Dotclear\Plugin\relatedEntries;

use Dotclear\App;
use Dotclear\Core\Backend\Filter\FilterPosts;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

class Manage extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE));

        return self::status();
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (isset($_POST['entries'])) {
            if (isset($_POST['id'])) {
                // Save Post relatedEntries
                try {
                    $meta    = App::meta();
                    $entries = implode(', ', $_POST['entries']);
                    $id      = $_POST['id'];

                    foreach ($meta->splitMetaValues($entries) as $tag) {
                        $meta->delPostMeta($id, 'relatedEntries', $tag);
                        $meta->setPostMeta($id, 'relatedEntries', $tag);
                    }
                    foreach ($meta->splitMetaValues($entries) as $tag) {
                        $r_tags = $meta->getMetaStr(serialize($tag), 'relatedEntries');
                        $r_tags = explode(', ', $r_tags);
                        array_push($r_tags, $id);
                        $r_tags = implode(', ', $r_tags);
                        foreach ($meta->splitMetaValues($r_tags) as $tags) {
                            $meta->delPostMeta($tag, 'relatedEntries', $tags);
                            $meta->setPostMeta($tag, 'relatedEntries', $tags);
                        }
                    }

                    Http::redirect(App::postTypes()->get('post')->adminUrl($id, false, ['add' => 1,'upd' => 1]));
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            } else {
                //Remove related posts links
                try {
                    $tags = [];
                    $meta = App::meta();

                    foreach ($_POST['entries'] as $id) {
                        // Get tags for post
                        $post_meta = $meta->getMetadata([
                            'meta_type' => 'relatedEntries',
                            'post_id'   => $id, ]);
                        $pm = [];
                        while ($post_meta->fetch()) {
                            $pm[] = $post_meta->meta_id;
                        }
                        foreach ($pm as $tag) {
                            $meta->delPostMeta($id, 'relatedEntries', $tag);
                            $meta->delPostMeta($tag, 'relatedEntries', $id);
                        }
                    }

                    My::redirect(['upd' => 2]);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        // Filters
        App::backend()->post_filter = new FilterPosts();

        // get list params
        $params = App::backend()->post_filter->params();

        App::backend()->posts      = null;
        App::backend()->posts_list = null;

        App::backend()->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        App::backend()->nb_per_page = UserPref::getUserFilters('pages', 'nb');

        if (isset($_GET['id']) && isset($_GET['addlinks']) && $_GET['addlinks'] == 1) {
            /*
            *   List of posts to be linked to current
            */

            // Get current post

            try {
                $post_id                 = (int) $_GET['id'];
                $my_params['post_id']    = $post_id;
                $my_params['no_content'] = true;
                $my_params['post_type']  = ['post'];
                $rs                      = App::blog()->getPosts($my_params);
                $post_title              = $rs->post_title;
                $post_type               = $rs->post_type;
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            // Get posts without current

            try {
                $params['no_content']      = true;
                $params['exclude_post_id'] = $post_id;
                App::backend()->posts      = App::blog()->getPosts($params);
                App::backend()->counter    = App::blog()->getPosts($params, true);
                App::backend()->posts_list = new BackendList(App::backend()->posts, App::backend()->counter->f(0));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            Page::openModule(
                __('Related entries'),
                Page::jsLoad('js/_posts_list.js') .
                App::backend()->post_filter->js(App::backend()->url()->get('admin.plugin', ['p' => My::id(),'id' => $post_id, 'addlinks' => '1'], '&'))
            );

            App::backend()->page_title = __('Add links');

            echo Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name) => '',
                    __('Related posts')                 => App::backend()->getPageURL(),
                    App::backend()->page_title          => '',
                ]
            ) .
            Notices::getNotices();

            if (!App::error()->flag()) {
                echo
                (new Text('h3', __('Select posts related to entry:') . '&nbsp;'))
                ->items([
                    (new Link())
                        ->href(App::postTypes()->get($post_type)->adminUrl($post_id))
                        ->text($post_title),
                ])
                ->render();

                $hidden = (new Para())
                    ->items([(new Hidden('addlinks', '1')),
                        (new Hidden('id', (string) $post_id)),
                        (new Hidden('p', (string) My::id())),
                    ])
                ->render();

                App::backend()->post_filter->display('admin.plugin.' . My::id(), $hidden);

                $block = (new Form('form-entries'))
                    ->method('post')
                    ->action(My::manageUrl())
                    ->fields([
                        (new Text(null, '%s')), // Here will go the posts list
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())->class(['col', 'checkboxes-helpers']),
                                (new Para())
                                    ->class(['col', 'right', 'form-buttons'])
                                    ->items([
                                        (new Hidden('id', (string) $post_id)),
                                        (new Submit('delete', __('Add links to selected posts')))->id('do-action'),
                                        App::nonce()->formNonce(),
                                        ... App::backend()->url()->hiddenFormFields('admin.plugin.' . My::id(), App::backend()->post_filter->values()),
                                    ]),
                            ]),
                    ])
                ->render();

                App::backend()->posts_list->display(
                    App::backend()->post_filter->page,
                    App::backend()->post_filter->nb,
                    $block,
                    App::backend()->post_filter->show()
                );
            }

            Page::helpBlock('posts');
            Page::closeModule();
        } else {
            /*
            * List of related posts
            */

            // Get posts with related posts

            try {
                $params['no_content']      = true;
                $params['sql']             = 'AND P.post_id IN (SELECT META.post_id FROM ' . App::con()->prefix() . 'meta META WHERE META.post_id = P.post_id ' . "AND META.meta_type = 'relatedEntries' ) ";
                App::backend()->posts      = App::blog()->getPosts($params);
                App::backend()->counter    = App::blog()->getPosts($params, true);
                App::backend()->posts_list = new BackendList(App::backend()->posts, App::backend()->counter->f(0));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            Page::openModule(
                __('Related entries'),
                Page::jsLoad('js/_posts_list.js') .
                App::backend()->post_filter->js(App::backend()->url()->get('admin.plugin', ['p' => My::id()], '&'))
            );

            echo Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name) => '',
                    __('Related posts')                 => '',
                ]
            ) .
            Notices::getNotices();

            if (isset($_GET['upd']) && $_GET['upd'] == 2) {
                Notices::success(__('Links have been successfully removed'));
            }

            // Related posts list

            App::backend()->post_filter->display('admin.plugin.' . My::id());

            $block = (new Form('form-entries'))
                ->method('post')
                ->action(My::manageUrl())
                ->fields([
                    (new Text(null, '%s')), // Here will go the posts list
                    (new Div())
                        ->class('two-cols')
                        ->items([
                            (new Para())->class(['col', 'checkboxes-helpers']),
                            (new Para())
                                ->class(['col', 'right', 'form-buttons'])
                                ->items([

                                    (new Submit('delete', __('Remove all links from selected posts')))->class(['delete'])->id('do-action'),
                                    App::nonce()->formNonce(),
                                    ... App::backend()->url()->hiddenFormFields('admin.plugin.' . My::id(), App::backend()->post_filter->values()),
                                ]),
                        ]),
                ])
            ->render();

            App::backend()->posts_list->display(
                App::backend()->post_filter->page,
                App::backend()->post_filter->nb,
                $block,
                App::backend()->post_filter->show()
            );

            Page::helpBlock('manage');
            Page::closeModule();
        }
    }
}
