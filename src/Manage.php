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

use dcCore;
use dcNsProcess;
use adminUserPref;
use dcPage;
use Exception;
use form;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use dcMedia;
use adminPostList;
use adminPostFilter;

class Manage extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE);

        $settings = dcCore::app()->blog->settings->get(My::id());

        return static::$init;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        $settings = dcCore::app()->blog->settings->get(My::id());

        // Image size combo
        $img_size_combo = [];
        $media          = new dcMedia();

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

        dcCore::app()->admin->default_tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

        /*
         * Admin page params.
         */
        dcCore::app()->admin->from_combo     = $from_combo;
        dcCore::app()->admin->img_size_combo = $img_size_combo;
        dcCore::app()->admin->alt_combo      = $alt_combo;
        dcCore::app()->admin->legend_combo   = $legend_combo;
        dcCore::app()->admin->html_tag_combo = $html_tag_combo;
        dcCore::app()->admin->link_combo     = $link_combo;
        dcCore::app()->admin->bubble_combo   = $bubble_combo;
        

        // Saving configurations
        if (isset($_POST['save'])) {
            $settings->put('relatedEntries_enabled', !empty($_POST['relatedEntries_enabled']));
            $settings->put('relatedEntries_title', Html::escapeHTML($_POST['relatedEntries_title']));
            $settings->put('relatedEntries_beforePost', !empty($_POST['relatedEntries_beforePost']));
            $settings->put('relatedEntries_afterPost', !empty($_POST['relatedEntries_afterPost']));
            $settings->put('relatedEntries_images', !empty($_POST['relatedEntries_images']));

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

            $settings->put('relatedEntries_images_options', serialize($opts));

            dcCore::app()->blog->triggerBlog();
            Http::redirect(dcCore::app()->admin->getPageURL() . '&upd=1');
        }

        if (isset($_POST['entries'])) {
            if (isset($_POST['id'])) {
                // Save Post relatedEntries
                try {
                    $meta    = dcCore::app()->meta;
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

                    Http::redirect(dcCore::app()->getPostAdminURL('post', $id) . '&add=1&upd=1');
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            } else {
                //Remove related posts links
                try {
                    $tags = [];
                    $meta = dcCore::app()->meta;

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

                    Http::redirect(dcCore::app()->admin->getPageURL() . '&upd=2&tab=postslist');
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
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
        if (!static::$init) {
            return;
        }

        $settings = dcCore::app()->blog->settings->get(My::id());

        // Filters
        dcCore::app()->admin->post_filter = new adminPostFilter();

        // get list params
        $params = dcCore::app()->admin->post_filter->params();

        dcCore::app()->admin->posts      = null;
        dcCore::app()->admin->posts_list = null;

        dcCore::app()->admin->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        dcCore::app()->admin->nb_per_page = adminUserPref::getUserFilters('pages', 'nb');

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
                $rs                      = dcCore::app()->blog->getPosts($my_params);
                $post_title              = $rs->post_title;
                $post_type               = $rs->post_type;
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            // Get posts without current

            try {
                $params['no_content']            = true;
                $params['exclude_post_id']       = $post_id;
                dcCore::app()->admin->posts      = dcCore::app()->blog->getPosts($params);
                dcCore::app()->admin->counter    = dcCore::app()->blog->getPosts($params, true);
                dcCore::app()->admin->posts_list = new adminPostList(dcCore::app()->admin->posts, dcCore::app()->admin->counter->f(0));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            dcPage::openModule(
                __('Related entries'),
                dcPage::jsLoad('js/_posts_list.js') .
                dcCore::app()->admin->post_filter->js(dcCore::app()->admin->getPageURL() . '&amp;id=' . $post_id . '&amp;addlinks=1')
            );

            dcCore::app()->admin->page_title = __('Add links');

            echo dcPage::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Related posts')                         => dcCore::app()->admin->getPageURL(),
                    dcCore::app()->admin->page_title            => '',
                ]
            ) .
            dcPage::notices();

            if (!dcCore::app()->error->flag()) {
                echo '<h3>' . __('Select posts related to entry:') . ' <a href="' . dcCore::app()->getPostAdminURL($post_type, $post_id) . '">' . $post_title . '</a></h3>';

                // Show posts

                # filters
                dcCore::app()->admin->post_filter->display('admin.plugin.' . My::id(), '<input type="hidden" name="p" value="relatedEntries" /><input type="hidden" name="addlinks" value="1" /><input type="hidden" name="id" value="' . $post_id . '" />');

                dcCore::app()->admin->posts_list->display(
                    dcCore::app()->admin->post_filter->page,
                    dcCore::app()->admin->post_filter->nb,
                    '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="form-entries">' .

                    '%s' .

                    '<div class="two-cols">' .
                    '<p class="col checkboxes-helpers"></p>' .

                    '<p class="col right">' .
                    '<input type="submit" value="' . __('Add links to selected posts') . '" /> <a class="button reset" href="post.php?id=' . $post_id . '">' . __('Cancel') . '</a></p>' .
                    '<p>' .
                    form::hidden(['addlinks'], true) .
                    form::hidden(['id'], $post_id) .
                    dcCore::app()->adminurl->getHiddenFormFields('admin.plugin.' . My::id(), dcCore::app()->admin->post_filter->values()) .
                    dcCore::app()->formNonce() . '</p>' .
                    '</div>' .
                    '</form>',
                    dcCore::app()->admin->post_filter->show()
                );
            }

            dcPage::helpBlock('posts');
            dcPage::closeModule();
        } else {
            /*
            * Config and list of related posts tabs
            */

            if (isset($_GET['page'])) {
                dcCore::app()->admin->default_tab = 'postslist';
            }

            // Get posts with related posts

            try {
                $params['no_content']            = true;
                $params['sql']                   = 'AND P.post_id IN (SELECT META.post_id FROM ' . dcCore::app()->prefix . 'meta META WHERE META.post_id = P.post_id ' . "AND META.meta_type = 'relatedEntries' ) ";
                dcCore::app()->admin->posts      = dcCore::app()->blog->getPosts($params);
                dcCore::app()->admin->counter    = dcCore::app()->blog->getPosts($params, true);
                dcCore::app()->admin->posts_list = new BackendList(dcCore::app()->admin->posts, dcCore::app()->admin->counter->f(0));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            dcPage::openModule(
                __('Related entries'),
                dcPage::jsLoad('js/_posts_list.js') .
                dcCore::app()->admin->post_filter->js(dcCore::app()->admin->getPageURL() . '#postslist') .
                dcPage::jsPageTabs(dcCore::app()->admin->default_tab) .
                dcPage::jsConfirmClose('config-form')
            );

            echo dcPage::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Related posts')                         => '',
                ]
            ) .
            dcPage::notices();

            if (isset($_GET['upd']) && $_GET['upd'] == 1) {
                dcPage::success(__('Configuration successfully saved'));
            } elseif (isset($_GET['upd']) && $_GET['upd'] == 2) {
                dcPage::success(__('Links have been successfully removed'));
            }

            $images = unserialize($settings->relatedEntries_images_options);

            // Config tab

            echo
            '<div class="multi-part" id="parameters" title="' . __('Parameters') . '">' .
            '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="config-form">' .
            '<div class="fieldset"><h3>' . __('Activation') . '</h3>' .
                '<p><label class="classic" for="relatedEntries_enabled">' .
                form::checkbox('relatedEntries_enabled', '1', $settings->relatedEntries_enabled) .
                __('Enable related posts on this blog') . '</label></p>' .
            '</div>' .
            '<div class="fieldset"><h3>' . __('Display options') . '</h3>' .
                '<p class="field"><label class="maximal" for="relatedEntries_title">' . __('Block title:') . '&nbsp;' .
                form::field('relatedEntries_title', 40, 255, Html::escapeHTML($settings->relatedEntries_title)) .
                '</label></p>' .
                '<p><label class="classic" for="relatedEntries_beforePost">' .
                form::checkbox('relatedEntries_beforePost', '1', $settings->relatedEntries_beforePost) .
                __('Display block before post content') . '</label></p>' .
                '<p><label class="classic" for="relatedEntries_afterPost">' .
                form::checkbox('relatedEntries_afterPost', '1', $settings->relatedEntries_afterPost) .
                __('Display block after post content') . '</label></p>' .
                '<p class="form-note info clear">' . __('Uncheck both boxes to use only the presentation widget.') . '</p>' .
            '</div>' .
            '<div class="fieldset"><h3>' . __('Images extracting options') . '</h3>';

            if (dcCore::app()->plugins->moduleExists('listImages')) {
                echo
                '<p><label class="classic" for="relatedEntries_images">' .
                form::checkbox('relatedEntries_images', '1', $settings->relatedEntries_images) .
                __('Extract images from related posts') . '</label></p>' .

                '<div class="two-boxes odd">' .

                '<p><label for="from">' . __('Images origin:') . '</label>' .
                form::combo(
                    'from',
                    dcCore::app()->admin->from_combo,
                    ($images['from'] != '' ? $images['from'] : 'image')
                ) .
                '</p>' .

                '<p><label for="size">' . __('Image size:') . '</label>' .
                form::combo(
                    'size',
                    dcCore::app()->admin->img_size_combo,
                    ($images['size'] != '' ? $images['size'] : 't')
                ) .
                '</p>' .

                '<p><label for="img_dim">' .
                form::checkbox('img_dim', '1', $images['img_dim']) .
                __('Include images dimensions') . '</label></p>' .

                '<p><label for="alt">' . __('Images alt attribute:') . '</label>' .
                form::combo(
                    'alt',
                    dcCore::app()->admin->alt_combo,
                    ($images['alt'] != '' ? $images['alt'] : 'inherit')
                ) .
                '</p>' .

                '<p><label for="start">' . __('First image to extract:') . '</label>' .
                    form::field('start', 3, 3, $images['start']) .
                '</p>' .

                '<p><label for="length">' . __('Number of images to extract:') . '</label>' .
                    form::field('length', 3, 3, $images['length']) .
                '</p>' .

                '</div><div class="two-boxes even">' .

                '<p><label for="legend">' . __('Legend:') . '</label>' .
                form::combo(
                    'legend',
                    dcCore::app()->admin->legend_combo,
                    ($images['legend'] != '' ? $images['legend'] : 'none')
                ) .
                '</p>' .

                '<p><label for="html_tag">' . __('HTML tag around image:') . '</label>' .
                form::combo(
                    'html_tag',
                    dcCore::app()->admin->html_tag_combo,
                    ($images['html_tag'] != '' ? $images['html_tag'] : 'div')
                ) .
                '</p>' .

                '<p><label for="class">' . __('CSS class on images:') . '</label>' .
                    form::field('class', 10, 10, $images['class']) .
                '</p>' .

                '<p><label for="link">' . __('Links destination:') . '</label>' .
                form::combo(
                    'link',
                    dcCore::app()->admin->link_combo,
                    ($images['link'] != '' ? $images['link'] : 'entry')
                ) .
                '</p>' .

                '<p><label for="bubble">' . __('Bubble:') . '</label>' .
                form::combo(
                    'bubble',
                    dcCore::app()->admin->bubble_combo,
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
            '<p class="clear"><input type="submit" name="save" value="' . __('Save configuration') . '" />' . dcCore::app()->formNonce() . '</p>' .
            '</form>' .
            '</div>' .

            // Related posts list tab

            '<div class="multi-part" id="postslist" title="' . __('Related posts list') . '">';

            dcCore::app()->admin->post_filter->display('admin.plugin.' . My::id(), '<input type="hidden" name="p" value="relatedEntries" /><input type="hidden" name="tab" value="postslist" />');

            // Show posts
            dcCore::app()->admin->posts_list->display(
                dcCore::app()->admin->post_filter->page,
                dcCore::app()->admin->post_filter->nb,
                '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="form-entries">' .

                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right">' .
                '<input type="submit" class="delete" value="' . __('Remove all links from selected posts') . '" /></p>' .
                '<p>' .
                dcCore::app()->adminurl->getHiddenFormFields('admin.plugin.' . My::id(), dcCore::app()->admin->post_filter->values()) .
                dcCore::app()->formNonce() . '</p>' .
                '</div>' .
                '</form>',
                dcCore::app()->admin->post_filter->show()
            );

            echo
            '</div>';

            dcPage::helpBlock('config');
            dcPage::closeModule();
        }
    }
}
