<?php
/**
 * @brief Related Entries, a plugin for Dotclear 2
 *
 * @package    Dotclear
 * @subpackage Plugins
 *
 * @author Philippe aka amalgame <philippe@dissitou.org>
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

        // Image size combo
        $img_size_combo = [];
        $media          = App::media();

        $img_size_combo[__('square')]    = 'sq';
        $img_size_combo[__('thumbnail')] = 't';
        $img_size_combo[__('small')]     = 's';
        $img_size_combo[__('medium')]    = 'm';
        $img_size_combo[__('original')]  = 'o';
        foreach ($media->thumb_sizes as $code => $size) {
            $img_size_combo[__($size[2])] = $code;
        }

        // Html tag combo
        $html_tag_combo = [
            __('div')    => 'div',
            __('li')     => 'li',
            __('no tag') => 'none',
        ];

        // Link combo
        $link_combo = [
            __('related posts')   => 'entry',
            __('original images') => 'image',
            __('no link')         => 'none',
        ];

        // Legend combo
        $legend_combo = [
            __('entry title') => 'entry',
            __('image title') => 'image',
            __('no legend')   => 'none',
        ];

        // Bubble combo
        $bubble_combo = [
            __('entry title') => 'entry',
            __('image title') => 'image',
            __('no bubble')   => 'none',
        ];

        // From combo
        $from_combo = [
            __('post excerpt') => 'excerpt',
            __('post content') => 'content',
            __('full post')    => 'full',
        ];

        // Alt combo
        $alt_combo = [
            __('image title') => 'inherit',
            __('no alt')      => 'none',
        ];

        App::backend()->default_tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

        /*
         * Admin page params.
         */
        App::backend()->from_combo     = $from_combo;
        App::backend()->img_size_combo = $img_size_combo;
        App::backend()->alt_combo      = $alt_combo;
        App::backend()->legend_combo   = $legend_combo;
        App::backend()->html_tag_combo = $html_tag_combo;
        App::backend()->link_combo     = $link_combo;
        App::backend()->bubble_combo   = $bubble_combo;

        // Saving configurations
        if (isset($_POST['save'])) {
            My::settings()->put('relatedEntries_enabled', !empty($_POST['relatedEntries_enabled']));
            My::settings()->put('relatedEntries_title', Html::escapeHTML($_POST['relatedEntries_title']));
            My::settings()->put('relatedEntries_beforePost', !empty($_POST['relatedEntries_beforePost']));
            My::settings()->put('relatedEntries_afterPost', !empty($_POST['relatedEntries_afterPost']));
            My::settings()->put('relatedEntries_images', !empty($_POST['relatedEntries_images']));

            $opts = [
                'size'     => !empty($_POST['size']) ? $_POST['size'] : 't',
                'html_tag' => !empty($_POST['html_tag']) ? $_POST['html_tag'] : 'div',
                'link'     => !empty($_POST['link']) ? $_POST['link'] : 'entry',
                'exif'     => 0,
                'legend'   => !empty($_POST['legend']) ? $_POST['legend'] : 'none',
                'bubble'   => !empty($_POST['bubble']) ? $_POST['bubble'] : 'image',
                'from'     => !empty($_POST['from']) ? $_POST['from'] : 'full',
                'start'    => !empty($_POST['start']) ? $_POST['start'] : 1,
                'length'   => !empty($_POST['length']) ? $_POST['length'] : 1,
                'class'    => !empty($_POST['class']) ? $_POST['class'] : '',
                'alt'      => !empty($_POST['alt']) ? $_POST['alt'] : 'inherit',
                'img_dim'  => !empty($_POST['img_dim']) ? $_POST['img_dim'] : 0,
            ];

            My::settings()->put('relatedEntries_images_options', serialize($opts));

            App::blog()->triggerBlog();
            My::redirect(['upd' => 1]);
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
                    '<form action="' . App::backend()->getPageURL() . '" method="post" id="form-entries">' .

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

            if (isset($_GET['upd']) && $_GET['upd'] == 1) {
                Notices::success(__('Configuration successfully saved'));
            } elseif (isset($_GET['upd']) && $_GET['upd'] == 2) {
                Notices::success(__('Links have been successfully removed'));
            }

            $images = unserialize(My::settings()->relatedEntries_images_options);

            // Config tab

            echo
            '<div class="multi-part" id="parameters" title="' . __('Parameters') . '">' .
            '<form action="' . App::backend()->getPageURL() . '" method="post" id="config-form">' .
            '<div class="fieldset"><h3>' . __('Activation') . '</h3>' .
                '<p><label class="classic" for="relatedEntries_enabled">' .
                form::checkbox('relatedEntries_enabled', '1', My::settings()->relatedEntries_enabled) .
                __('Enable related posts on this blog') . '</label></p>' .
            '</div>' .
            '<div class="fieldset"><h3>' . __('Display options') . '</h3>' .
                '<p class="field"><label class="maximal" for="relatedEntries_title">' . __('Block title:') . '&nbsp;' .
                form::field('relatedEntries_title', 40, 255, Html::escapeHTML(My::settings()->relatedEntries_title)) .
                '</label></p>' .
                '<p><label class="classic" for="relatedEntries_beforePost">' .
                form::checkbox('relatedEntries_beforePost', '1', My::settings()->relatedEntries_beforePost) .
                __('Display block before post content') . '</label></p>' .
                '<p><label class="classic" for="relatedEntries_afterPost">' .
                form::checkbox('relatedEntries_afterPost', '1', My::settings()->relatedEntries_afterPost) .
                __('Display block after post content') . '</label></p>' .
                '<p class="form-note info clear">' . __('Uncheck both boxes to use only the presentation widget.') . '</p>' .
            '</div>' .
            '<div class="fieldset"><h3>' . __('Images extracting options') . '</h3>';

            if (App::plugins()->moduleExists('listImages')) {
                echo
                '<div class="three-boxes">' .
                '<p><label class="classic" for="relatedEntries_images">' .
                form::checkbox('relatedEntries_images', '1', My::settings()->relatedEntries_images) .
                __('Extract images from related posts') . '</label></p>' .
                '</div>' .
                
                '<div class="three-boxes">' .

                '<p><label for="from">' . __('Images origin:') . '</label>' .
                form::combo(
                    'from',
                    App::backend()->from_combo,
                    ($images['from'] != '' ? $images['from'] : 'image')
                ) .
                '</p>' .

                '<p><label for="size">' . __('Image size:') . '</label>' .
                form::combo(
                    'size',
                    App::backend()->img_size_combo,
                    ($images['size'] != '' ? $images['size'] : 't')
                ) .
                '</p>' .

                '<p><label for="img_dim">' .
                form::checkbox('img_dim', '1', $images['img_dim']) .
                __('Include images dimensions') . '</label></p>' .

                '<p><label for="alt">' . __('Images alt attribute:') . '</label>' .
                form::combo(
                    'alt',
                    App::backend()->alt_combo,
                    ($images['alt'] != '' ? $images['alt'] : 'inherit')
                ) .
                '</p>' .

                '<p><label for="start">' . __('First image to extract:') . '</label>' .
                    form::field('start', 3, 3, $images['start']) .
                '</p>' .

                '<p><label for="length">' . __('Number of images to extract:') . '</label>' .
                    form::field('length', 3, 3, $images['length']) .
                '</p>' .

                '</div><div class="three-boxes">' .

                '<p><label for="legend">' . __('Legend:') . '</label>' .
                form::combo(
                    'legend',
                    App::backend()->legend_combo,
                    ($images['legend'] != '' ? $images['legend'] : 'none')
                ) .
                '</p>' .

                '<p><label for="html_tag">' . __('HTML tag around image:') . '</label>' .
                form::combo(
                    'html_tag',
                    App::backend()->html_tag_combo,
                    ($images['html_tag'] != '' ? $images['html_tag'] : 'div')
                ) .
                '</p>' .

                '<p><label for="class">' . __('CSS class on images:') . '</label>' .
                    form::field('class', 10, 10, $images['class']) .
                '</p>' .

                '<p><label for="link">' . __('Links destination:') . '</label>' .
                form::combo(
                    'link',
                    App::backend()->link_combo,
                    ($images['link'] != '' ? $images['link'] : 'entry')
                ) .
                '</p>' .

                '<p><label for="bubble">' . __('Bubble:') . '</label>' .
                form::combo(
                    'bubble',
                    App::backend()->bubble_combo,
                    ($images['bubble'] != '' ? $images['bubble'] : 'image')
                ) .
                '</p>' .

                '</div>' .

                '</div>';
            } else {
                echo
                '<p class="form-note info clear">' . __('Install or activate listImages plugin to be able to display links to related entries as images') . '</p>' .
                '</div>';
            }

            echo
            '<p class="clear"><input type="submit" name="save" value="' . __('Save configuration') . '" />' . App::nonce()->getFormNonce() . '</p>' .
            '</form>' .
            '</div>' .

            // Related posts list tab

            '<div class="multi-part" id="postslist" title="' . __('Related posts list') . '">';

            App::backend()->post_filter->display('admin.plugin.' . My::id(), '<input type="hidden" name="p" value="' . My::id() . '" /><input type="hidden" name="tab" value="postslist" />');

            // Show posts
            App::backend()->posts_list->display(
                App::backend()->post_filter->page,
                App::backend()->post_filter->nb,
                '<form action="' . App::backend()->getPageURL() . '" method="post" id="form-entries">' .

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

            echo
            '</div>';

            Page::helpBlock('config');
            Page::closeModule();
        }
    }
}
