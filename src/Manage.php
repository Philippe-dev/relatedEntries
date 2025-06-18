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

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\Notices;
use Exception;
use form;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Filter\FilterPosts;

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

                    My::redirect(['upd' => 2, 'tab' => 'postslist']);
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
                App::backend()->posts_list = new ListingPosts(App::backend()->posts, App::backend()->counter->f(0));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            Page::openModule(
                __('Related entries'),
                Page::jsLoad('js/_posts_list.js') .
                App::backend()->post_filter->js(App::backend()->url()->get('admin.plugin'). '&p=' . My::id() . '&id=' . $post_id . '&addlinks=1')
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
                echo '<h3>' . __('Select posts related to entry:') . ' <a href="' . App::postTypes()->get($post_type)->adminUrl($post_id) . '">' . $post_title . '</a></h3>';

                // Show posts

                # filters
                App::backend()->post_filter->display('admin.plugin.' . My::id(), '<input type="hidden" name="p" value="' . My::id() . '" /><input type="hidden" name="addlinks" value="1" /><input type="hidden" name="id" value="' . $post_id . '" />');

                App::backend()->posts_list->display(
                    App::backend()->post_filter->page,
                    App::backend()->post_filter->nb,
                    '<form action="' . My::manageUrl() . '" method="post" id="form-entries">' .

                    '%s' .

                    '<div class="two-cols">' .
                    '<p class="col checkboxes-helpers"></p>' .

                    '<p class="col right">' .
                    '<input type="submit" value="' . __('Add links to selected posts') . '" /> <a class="button reset" href="post.php?id=' . $post_id . '">' . __('Cancel') . '</a></p>' .
                    '<p>' .
                    form::hidden(['addlinks'], true) .
                    form::hidden(['id'], $post_id) .
                    App::backend()->url()->getHiddenFormFields('admin.plugin.' . My::id(), App::backend()->post_filter->values()) .
                    App::nonce()->getFormNonce() . '</p>' .
                    '</div>' .
                    '</form>',
                    App::backend()->post_filter->show()
                );
            }

            Page::helpBlock('posts');
            Page::closeModule();
        } else {
            /*
            * Config and list of related posts tabs
            */

            if (isset($_GET['page'])) {
                App::backend()->default_tab = 'postslist';
            }

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
                App::backend()->post_filter->js(App::backend()->url()->get('admin.plugin'). '&p=' . My::id() . '#postslist') .
                Page::jsPageTabs(App::backend()->default_tab) .
                Page::jsConfirmClose('config-form')
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

            

            // Related posts list tab

            '<div class="multi-part" id="postslist" title="' . __('Related posts list') . '">';

            App::backend()->post_filter->display('admin.plugin.' . My::id(), '<input type="hidden" name="p" value="' . My::id() . '" /><input type="hidden" name="tab" value="postslist" />');

            // Show posts
            App::backend()->posts_list->display(
                App::backend()->post_filter->page,
                App::backend()->post_filter->nb,
                '<form action="' . My::manageUrl() . '" method="post" id="form-entries">' .

                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right">' .
                '<input type="submit" class="delete" value="' . __('Remove all links from selected posts') . '" /></p>' .
                '<p>' .
                App::backend()->url()->getHiddenFormFields('admin.plugin.' . My::id(), App::backend()->post_filter->values()) .
                App::nonce()->getFormNonce() . '</p>' .
                '</div>' .
                '</form>',
                App::backend()->post_filter->show()
            );

           

            Page::helpBlock('config');
            Page::closeModule();
        }
    }
}
