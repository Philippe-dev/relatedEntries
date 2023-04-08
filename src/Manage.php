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
use dcBlog;
use dcPage;
use Exception;
use form;
use html;
use http;
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
        self::$init = true;

        dcCore::app()->addBehavior('adminColumnsListsV2', [BackendBehaviors::class, 'adminColumnsLists']);
        dcCore::app()->addBehavior('adminPostListHeaderV2', [BackendBehaviors::class, 'adminPostListHeader']);
        dcCore::app()->addBehavior('adminPostListValueV2', [BackendBehaviors::class, 'adminPostListValue']);
        dcCore::app()->addBehavior('adminPagesListHeaderV2', [BackendBehaviors::class, 'adminPagesListHeader']);
        dcCore::app()->addBehavior('adminPagesListValueV2', [BackendBehaviors::class, 'adminPagesListValue']);

        return self::$init;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        $settings = dcCore::app()->blog->settings->relatedEntries;

        if (is_null(dcCore::app()->blog->settings->relatedEntries->relatedEntries_enabled)) {
            try {
                // Add default settings values if necessary

                $settings->put('relatedEntries_enabled', false, 'boolean', 'Enable related entries', false, true);
                $settings->put('relatedEntries_images', false, 'boolean', 'Display related entries links as images', false, true);
                $settings->put('relatedEntries_beforePost', false, 'boolean', 'Display related entries before post content', false, true);
                $settings->put('relatedEntries_afterPost', true, 'boolean', 'Display related entries after post content', false, true);
                $settings->put('relatedEntries_title', __('Related posts'), 'string', 'Related entries block title', false, true);

                $opts = [
                    'size'     => 't',
                    'html_tag' => 'div',
                    'link'     => 'entry',
                    'exif'     => 0,
                    'legend'   => 'none',
                    'bubble'   => 'image',
                    'from'     => 'full',
                    'start'    => 1,
                    'length'   => 1,
                    'class'    => '',
                    'alt'      => 'inherit',
                    'img_dim'  => 0,
                ];

                $settings->put('relatedEntries_images_options', serialize($opts), 'string', 'Related entries images options', false, true);

                dcCore::app()->blog->triggerBlog();
                http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

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

        $show_filters = false;

        /*
         * Admin page params.
         */
        dcCore::app()->admin->show_filters   = $show_filters;
        dcCore::app()->admin->from_combo     = $from_combo;
        dcCore::app()->admin->img_size_combo = $img_size_combo;
        dcCore::app()->admin->alt_combo      = $alt_combo;
        dcCore::app()->admin->legend_combo   = $legend_combo;
        dcCore::app()->admin->html_tag_combo = $html_tag_combo;
        dcCore::app()->admin->link_combo     = $link_combo;
        dcCore::app()->admin->bubble_combo   = $bubble_combo;
        dcCore::app()->admin->settings       = $settings;
        $id                                  = !empty($_GET['id']) ? $_GET['id'] : '';
        dcCore::app()->admin->id             = $id;

        // Saving configurations
        if (isset($_POST['save'])) {
            dcCore::app()->admin->settings->put('relatedEntries_enabled', !empty($_POST['relatedEntries_enabled']));
            dcCore::app()->admin->settings->put('relatedEntries_title', html::escapeHTML($_POST['relatedEntries_title']));
            dcCore::app()->admin->settings->put('relatedEntries_beforePost', !empty($_POST['relatedEntries_beforePost']));
            dcCore::app()->admin->settings->put('relatedEntries_afterPost', !empty($_POST['relatedEntries_afterPost']));
            dcCore::app()->admin->settings->put('relatedEntries_images', !empty($_POST['relatedEntries_images']));

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

            dcCore::app()->admin->settings->put('relatedEntries_images_options', serialize($opts));

            dcCore::app()->blog->triggerBlog();
            http::redirect(dcCore::app()->admin->getPageURL() . '&upd=1');
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

                    http::redirect(DC_ADMIN_URL . 'post.php?id=' . $id . '&add=1&upd=1');
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

                    http::redirect(dcCore::app()->admin->getPageURL() . '&upd=2&tab=postslist');
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
        if (!self::$init) {
            return;
        }

        if (isset($_GET['id']) && isset($_GET['addlinks']) && $_GET['addlinks'] == 1 || isset($_GET['relatedEntries_filters'])) {
            try {
                $id                      = (int) $_GET['id'];
                $my_params['post_id']    = $id;
                $my_params['no_content'] = true;
                $my_params['post_type']  = ['post'];

                $rs         = dcCore::app()->blog->getPosts($my_params);
                $post_title = $rs->post_title;
                $post_type  = $rs->post_type;
                $post_id    = $rs->post_id;
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            // Filters
            // -------
            dcCore::app()->admin->post_filter = new adminPostFilter();

            // get list params
            $params = dcCore::app()->admin->post_filter->params();

            dcCore::app()->admin->posts      = null;
            dcCore::app()->admin->posts_list = null;

            // Get posts without current

            if (isset($_GET['id'])) {
                try {
                    $id                              = $_GET['id'];
                    $params['no_content']            = true;
                    $params['exclude_post_id']       = $id;
                    dcCore::app()->admin->posts      = dcCore::app()->blog->getPosts($params);
                    dcCore::app()->admin->counter    = dcCore::app()->blog->getPosts($params, true);
                    dcCore::app()->admin->posts_list = new adminPostList(dcCore::app()->admin->posts, dcCore::app()->admin->counter->f(0));
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }

            dcCore::app()->admin->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            dcCore::app()->admin->nb_per_page = adminUserPref::getUserFilters('pages', 'nb');

            echo
            '<html>' .
            '<head>' ;

            $form_filter_title = __('Show filters and display options');
            $starting_script   = dcPage::jsLoad('js/_posts_list.js');
            $starting_script .= dcPage::jsLoad(DC_ADMIN_URL . '?pf=relatedEntries/js/posts-filter-controls.js');
            $starting_script .= '<script>' . "\n" .
            '//<![CDATA[' . "\n" .
            dcPage::jsVar('dotclear.msg.show_filters', dcCore::app()->admin->show_filters ? 'true' : 'false') . "\n" .
            dcPage::jsVar('dotclear.msg.filter_posts_list', $form_filter_title) . "\n" .
            dcPage::jsVar('dotclear.msg.cancel_the_filter', __('Cancel filters and display options')) . "\n" .
            dcPage::jsVar('filter_reset_url', dcCore::app()->admin->getPageURL()) . "\n" .
            dcPage::jsVar('id', $id) . "\n" .
            '//]]>' .
            '</script>';
            echo $starting_script;

            echo
            '<title>' . __('Related posts') . '</title>' .
            '</head>' .
            '<body>';

            if (!dcCore::app()->error->flag()) {
                if (dcCore::app()->admin->id) {
                    switch (dcCore::app()->admin->status) {
                        case dcBlog::POST_PUBLISHED:
                            $img_status = sprintf((string) dcCore::app()->admin->img_status_pattern, __('Published'), 'check-on.png');

                            break;
                        case dcBlog::POST_UNPUBLISHED:
                            $img_status = sprintf((string) dcCore::app()->admin->img_status_pattern, __('Unpublished'), 'check-off.png');

                            break;
                        case dcBlog::POST_SCHEDULED:
                            $img_status = sprintf((string) dcCore::app()->admin->img_status_pattern, __('Scheduled'), 'scheduled.png');

                            break;
                        case dcBlog::POST_PENDING:
                            $img_status = sprintf((string) dcCore::app()->admin->img_status_pattern, __('Pending'), 'check-wrn.png');

                            break;
                        default:
                            $img_status = '';
                    }
                    echo '&nbsp;&nbsp;&nbsp;' . $img_status;
                }

                echo dcPage::breadcrumb(
                    [
                        html::escapeHTML(dcCore::app()->blog->name) => '',
                        __('Related posts')                         => dcCore::app()->admin->getPageURL(),
                        dcCore::app()->admin->page_title            => '',
                    ]
                ) .
                    '<h3>' . __('Select posts related to entry:') . ' <a href="' . dcCore::app()->getPostAdminURL($post_type, $post_id) . '">' . $post_title . '</a></h3>';

                // Show posts
                if (!isset(dcCore::app()->admin->posts_list) || empty(dcCore::app()->admin->posts_list)) {
                    echo '<p><strong>' . __('No related posts') . '</strong></p>';
                } else {
                    # filters
                    dcCore::app()->admin->post_filter->display('admin.plugin.relatedEntries', '<input type="hidden" name="p" value="relatedEntries" /><input type="hidden" name="addlinks" value="1" /><input type="hidden" name="id" value="' . $id . '" />');

                    dcCore::app()->admin->posts_list->display(
                        dcCore::app()->admin->post_filter->page,
                        dcCore::app()->admin->post_filter->nb,
                        '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="form-entries">' .

                        '%s' .

                        '<div class="two-cols">' .
                        '<p class="col checkboxes-helpers"></p>' .

                        '<p class="col right">' .
                        '<input type="submit" value="' . __('Add links to selected posts') . '" /> <a class="button reset" href="post.php?id=' . dcCore::app()->admin->id . '&upd=1">' . __('Cancel') . '</a></p>' .
                        '<p>' .
                        form::hidden(['addlinks'], true) .
                        form::hidden(['id'], dcCore::app()->admin->id) .
                        form::hidden(['p'], 'relatedEntries') .
                        dcCore::app()->adminurl->getHiddenFormFields('admin.plugin.relatedEntries', dcCore::app()->admin->post_filter->values()) .
                        dcCore::app()->formNonce() . '</p>' .
                        '</div>' .
                        '</form>',
                        dcCore::app()->admin->post_filter->show()
                    );
                }
            }
            dcPage::helpBlock('relatedEntriesposts');
        } else {
            if (isset($_GET['page'])) {
                dcCore::app()->admin->default_tab = 'postslist';
            }

            // Filters
            dcCore::app()->admin->post_filter = new adminPostFilter();

            // get list params
            $params = dcCore::app()->admin->post_filter->params();

            dcCore::app()->admin->posts      = null;
            dcCore::app()->admin->posts_list = null;

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

            dcCore::app()->admin->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            dcCore::app()->admin->nb_per_page = adminUserPref::getUserFilters('pages', 'nb');

            echo
            '<html>' .
            '<head>' ;

            $form_filter_title = __('Show filters and display options');
            $starting_script   = dcPage::jsLoad('js/_posts_list.js');
            $starting_script .= dcPage::jsLoad(DC_ADMIN_URL . '?pf=relatedEntries/js/filter-controls.js');
            $starting_script .= dcPage::jsPageTabs(dcCore::app()->admin->default_tab);
            $starting_script .= dcPage::jsConfirmClose('config-form');
            $starting_script .= '<script>' . "\n" .
            '//<![CDATA[' . "\n" .
            dcPage::jsVar('dotclear.msg.show_filters', dcCore::app()->admin->show_filters ? 'true' : 'false') . "\n" .
            dcPage::jsVar('dotclear.msg.filter_posts_list', $form_filter_title) . "\n" .
            dcPage::jsVar('dotclear.msg.cancel_the_filter', __('Cancel filters and display options')) . "\n" .
            dcPage::jsVar('filter_reset_url', dcCore::app()->admin->getPageURL()) . "\n" .
            '//]]>' .
            '</script>';
            echo $starting_script;

            echo
            '<title>' . __('Related posts') . '</title>' .
            '</head>' .
            '<body>';

            echo dcPage::breadcrumb(
                [
                    html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Related posts')                         => '',
                ]
            );

            if (isset($_GET['upd']) && $_GET['upd'] == 1) {
                dcPage::success(__('Configuration successfully saved'));
            } elseif (isset($_GET['upd']) && $_GET['upd'] == 2) {
                dcPage::success(__('Links have been successfully removed'));
            }

            $as = unserialize(dcCore::app()->admin->settings->relatedEntries_images_options);

            //Parameters tab

            echo
            '<div class="multi-part" id="parameters" title="' . __('Parameters') . '">' .
            '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="config-form">' .
            '<div class="fieldset"><h3>' . __('Activation') . '</h3>' .
                '<p><label class="classic" for="relatedEntries_enabled">' .
                form::checkbox('relatedEntries_enabled', '1', dcCore::app()->admin->settings->relatedEntries_enabled) .
                __('Enable related posts on this blog') . '</label></p>' .
            '</div>' .
            '<div class="fieldset"><h3>' . __('Display options') . '</h3>' .
                '<p class="field"><label class="maximal" for="relatedEntries_title">' . __('Block title:') . '&nbsp;' .
                form::field('relatedEntries_title', 40, 255, html::escapeHTML(dcCore::app()->admin->settings->relatedEntries_title)) .
                '</label></p>' .
                '<p><label class="classic" for="relatedEntries_beforePost">' .
                form::checkbox('relatedEntries_beforePost', '1', dcCore::app()->admin->settings->relatedEntries_beforePost) .
                __('Display block before post content') . '</label></p>' .
                '<p><label class="classic" for="relatedEntries_afterPost">' .
                form::checkbox('relatedEntries_afterPost', '1', dcCore::app()->admin->settings->relatedEntries_afterPost) .
                __('Display block after post content') . '</label></p>' .
                '<p class="form-note info clear">' . __('Uncheck both boxes to use only the presentation widget.') . '</p>' .
            '</div>' .
            '<div class="fieldset"><h3>' . __('Images extracting options') . '</h3>';

            if (dcCore::app()->plugins->moduleExists('listImages')) {
                echo
                '<p><label class="classic" for="relatedEntries_images">' .
                form::checkbox('relatedEntries_images', '1', dcCore::app()->admin->settings->relatedEntries_images) .
                __('Extract images from related posts') . '</label></p>' .

                '<div class="two-boxes odd">' .

                '<p><label for="from">' . __('Images origin:') . '</label>' .
                form::combo(
                    'from',
                    dcCore::app()->admin->from_combo,
                    ($as['from'] != '' ? $as['from'] : 'image')
                ) .
                '</p>' .

                '<p><label for="size">' . __('Image size:') . '</label>' .
                form::combo(
                    'size',
                    dcCore::app()->admin->img_size_combo,
                    ($as['size'] != '' ? $as['size'] : 't')
                ) .
                '</p>' .

                '<p><label for="img_dim">' .
                form::checkbox('img_dim', '1', $as['img_dim']) .
                __('Include images dimensions') . '</label></p>' .

                '<p><label for="alt">' . __('Images alt attribute:') . '</label>' .
                form::combo(
                    'alt',
                    dcCore::app()->admin->alt_combo,
                    ($as['alt'] != '' ? $as['alt'] : 'inherit')
                ) .
                '</p>' .

                '<p><label for="start">' . __('First image to extract:') . '</label>' .
                    form::field('start', 3, 3, $as['start']) .
                '</p>' .

                '<p><label for="length">' . __('Number of images to extract:') . '</label>' .
                    form::field('length', 3, 3, $as['length']) .
                '</p>' .

                '</div><div class="two-boxes even">' .

                '<p><label for="legend">' . __('Legend:') . '</label>' .
                form::combo(
                    'legend',
                    dcCore::app()->admin->legend_combo,
                    ($as['legend'] != '' ? $as['legend'] : 'none')
                ) .
                '</p>' .

                '<p><label for="html_tag">' . __('HTML tag around image:') . '</label>' .
                form::combo(
                    'html_tag',
                    dcCore::app()->admin->html_tag_combo,
                    ($as['html_tag'] != '' ? $as['html_tag'] : 'div')
                ) .
                '</p>' .

                '<p><label for="class">' . __('CSS class on images:') . '</label>' .
                    form::field('class', 10, 10, $as['class']) .
                '</p>' .

                '<p><label for="link">' . __('Links destination:') . '</label>' .
                form::combo(
                    'link',
                    dcCore::app()->admin->link_combo,
                    ($as['link'] != '' ? $as['link'] : 'entry')
                ) .
                '</p>' .

                '<p><label for="bubble">' . __('Bubble:') . '</label>' .
                form::combo(
                    'bubble',
                    dcCore::app()->admin->bubble_combo,
                    ($as['bubble'] != '' ? $as['bubble'] : 'image')
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

            //Related posts list tab

            '<div class="multi-part" id="postslist" title="' . __('Related posts list') . '">';

            if (!isset(dcCore::app()->admin->posts_list) || empty(dcCore::app()->admin->posts_list)) {
                echo '<p><strong>' . __('No related posts') . '</strong></p>';
            } else {
                dcCore::app()->admin->post_filter->display('admin.plugin.relatedEntries', '<input type="hidden" name="p" value="relatedEntries" /><input type="hidden" name="tab" value="postslist" />');

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
                    '<input type="hidden" name="p" value="relatedEntries" />' .
                    form::hidden(['tab'], 'postslist') .
                    form::hidden(['p'], 'relatedEntries') .
                    dcCore::app()->adminurl->getHiddenFormFields('admin.plugin.relatedEntries', dcCore::app()->admin->post_filter->values()) .
                    dcCore::app()->formNonce() . '</p>' .
                    '</div>' .
                    '</form>',
                    dcCore::app()->admin->post_filter->show()
                );
            }

            echo
            '</div>';

            dcPage::helpBlock('relatedEntries');
        }

        echo
        '</body>' .
        '</html>';
    }
}
