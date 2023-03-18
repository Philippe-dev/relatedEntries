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
use dcUtils;
use dt;
use adminPostList;

class Manage extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        self::$init = true;

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

        // Getting categories
        try {
            $categories = dcCore::app()->blog->getCategories(['post_type' => 'post']);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        // Getting authors
        try {
            $users = dcCore::app()->blog->getPostsUsers();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        // Getting dates
        try {
            $dates = dcCore::app()->blog->getDates(['type' => 'month']);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        // Getting langs
        try {
            $langs = dcCore::app()->blog->getLangs();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        // Creating filter combo boxes
        if (!dcCore::app()->error->flag()) {
            // Filter form we'll put in html_block
            $users_combo      = $categories_combo = [];
            $users_combo['-'] = $categories_combo['-'] = '';
            while ($users->fetch()) {
                $user_cn = dcUtils::getUserCN(
                    $users->user_id,
                    $users->user_name,
                    $users->user_firstname,
                    $users->user_displayname
                );

                if ($user_cn != $users->user_id) {
                    $user_cn .= ' (' . $users->user_id . ')';
                }

                $users_combo[$user_cn] = $users->user_id;
            }

            $categories_combo[__('None')] = 'NULL';
            while ($categories->fetch()) {
                $categories_combo[str_repeat('&nbsp;&nbsp;', $categories->level - 1) . ($categories->level - 1 == 0 ? '' : '&bull; ') .
                    html::escapeHTML($categories->cat_title) .
                    ' (' . $categories->nb_post . ')'] = $categories->cat_id;
            }

            $status_combo = [
                '-' => '',
            ];
            foreach (dcCore::app()->blog->getAllPostStatus() as $k => $v) {
                $status_combo[$v] = (string) $k;
            }

            $selected_combo = [
                '-'                => '',
                __('selected')     => '1',
                __('not selected') => '0',
            ];

            // Months array
            $dt_m_combo['-'] = '';
            while ($dates->fetch()) {
                $dt_m_combo[dt::str('%B %Y', $dates->ts())] = $dates->year() . $dates->month();
            }

            $lang_combo['-'] = '';
            while ($langs->fetch()) {
                $lang_combo[$langs->post_lang] = $langs->post_lang;
            }

            $sortby_combo = [
                __('Date')     => 'post_dt',
                __('Title')    => 'post_title',
                __('Category') => 'cat_title',
                __('Author')   => 'user_id',
                __('Status')   => 'post_status',
                __('Selected') => 'post_selected',
            ];

            $order_combo = [
                __('Descending') => 'desc',
                __('Ascending')  => 'asc',
            ];
        }

        /* Get posts
        -------------------------------------------------------- */
        $id       = !empty($_GET['id']) ? $_GET['id'] : '';
        $user_id  = !empty($_GET['user_id']) ? $_GET['user_id'] : '';
        $cat_id   = !empty($_GET['cat_id']) ? $_GET['cat_id'] : '';
        $status   = $_GET['status']   ?? '';
        $selected = $_GET['selected'] ?? '';
        $month    = !empty($_GET['month']) ? $_GET['month'] : '';
        $entries  = !empty($_GET['entries']) ? $_GET['entries'] : '';
        $lang     = !empty($_GET['lang']) ? $_GET['lang'] : '';
        $sortby   = !empty($_GET['sortby']) ? $_GET['sortby'] : 'post_dt';
        $order    = !empty($_GET['order']) ? $_GET['order'] : 'desc';

        $show_filters = false;

        $page        = !empty($_GET['page']) ? (int) $_GET['page'] : 1;
        $nb_per_page = 30;

        if (!empty($_GET['nb']) && (int) $_GET['nb'] > 0) {
            if ($nb_per_page != $_GET['nb']) {
                $show_filters = true;
            }
            $nb_per_page = (int) $_GET['nb'];
        }

        $params['limit']      = [(($page - 1) * $nb_per_page), $nb_per_page];
        $params['no_content'] = true;

        // - User filter
        if ($user_id !== '' && in_array($user_id, $users_combo)) {
            $params['user_id'] = $user_id;
            $show_filters      = true;
        } else {
            $user_id = '';
        }

        // - Categories filter
        if ($cat_id !== '' && in_array($cat_id, $categories_combo)) {
            $params['cat_id'] = $cat_id;
            $show_filters     = true;
        } else {
            $cat_id = '';
        }

        // - Status filter
        if ($status !== '' && in_array($status, $status_combo)) {
            $params['post_status'] = $status;
            $show_filters          = true;
        } else {
            $status = '';
        }

        // - Selected filter
        if ($selected !== '' && in_array($selected, $selected_combo)) {
            $params['post_selected'] = $selected;
            $show_filters            = true;
        } else {
            $selected = '';
        }

        // - Month filter
        if ($month !== '' && in_array($month, $dt_m_combo)) {
            $params['post_month'] = substr($month, 4, 2);
            $params['post_year']  = substr($month, 0, 4);
            $show_filters         = true;
        } else {
            $month = '';
        }

        // - Lang filter
        if ($lang !== '' && in_array($lang, $lang_combo)) {
            $params['post_lang'] = $lang;
            $show_filters        = true;
        } else {
            $lang = '';
        }

        // - Sortby and order filter
        if ($sortby !== '' && in_array($sortby, $sortby_combo)) {
            if ($order !== '' && in_array($order, $order_combo)) {
                $params['order'] = $sortby . ' ' . $order;
            } else {
                $order = 'desc';
            }

            if ($sortby != 'post_dt' || $order != 'desc') {
                $show_filters = true;
            }
        } else {
            $sortby = 'post_dt';
            $order  = 'desc';
        }

        $default_tab = $_GET['tab'] ?? 'parameters';

        /*
         * Admin page params.
         */
        dcCore::app()->admin->default_tab    = $default_tab;
        dcCore::app()->admin->show_filters   = $show_filters;
        dcCore::app()->admin->from_combo     = $from_combo;
        dcCore::app()->admin->img_size_combo = $img_size_combo;
        dcCore::app()->admin->alt_combo      = $alt_combo;
        dcCore::app()->admin->legend_combo   = $legend_combo;
        dcCore::app()->admin->html_tag_combo = $html_tag_combo;
        dcCore::app()->admin->link_combo     = $link_combo;
        dcCore::app()->admin->bubble_combo   = $bubble_combo;
        dcCore::app()->admin->settings       = $settings;
        /*
         * Filters
         */
        dcCore::app()->admin->users_combo      = $users_combo;
        dcCore::app()->admin->user_id          = $user_id;
        dcCore::app()->admin->categories_combo = $categories_combo;
        dcCore::app()->admin->cat_id           = $cat_id;
        dcCore::app()->admin->status_combo     = $status_combo;
        dcCore::app()->admin->status           = $status;
        dcCore::app()->admin->selected_combo   = $selected_combo;
        dcCore::app()->admin->selected         = $selected;
        dcCore::app()->admin->dt_m_combo       = $dt_m_combo;
        dcCore::app()->admin->month            = $month;
        dcCore::app()->admin->lang_combo       = $lang_combo;
        dcCore::app()->admin->lang             = $lang;
        dcCore::app()->admin->sortby_combo     = $sortby_combo;
        dcCore::app()->admin->sortby           = $sortby;
        dcCore::app()->admin->order_combo      = $order_combo;
        dcCore::app()->admin->order            = $order;
        dcCore::app()->admin->id               = $id;
        /*
         * Posts list
         */
        //dcCore::app()->admin->page        = $page;
        //dcCore::app()->admin->nb_per_page = $nb_per_page;

        // Save Post relatedEntries

        if (isset($_POST['entries'])) {
            try {
                $entries = implode(', ', $_POST['entries']);
                $id      = $_POST['id'];

                $meta = dcCore::app()->meta;

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
        }

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
        //Remove related posts links

        if (isset($_POST['entries'])) {
            $meta = dcCore::app()->meta;

            try {
                $tags = [];

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

        if ((isset($_GET['id']) || isset($_POST['id'])) && isset($_GET['addlinks']) && $_GET['addlinks'] == 1) {
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

            dcCore::app()->admin->posts      = null;
            dcCore::app()->admin->posts_list = null;

            // Get posts without current

            if (isset($_GET['id'])) {
                try {
                    $id                              = $_GET['id'];
                    $params['no_content']            = true;
                    $params['exclude_post_id']       = $id;
                    dcCore::app()->admin->posts      = dcCore::app()->blog->getPosts($params);
                    $counter                         = dcCore::app()->blog->getPosts($params, true);
                    dcCore::app()->admin->posts_list = new AdminPostList(dcCore::app()->admin->posts, $counter->f(0));
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
            $starting_script .= dcPage::jsLoad(DC_ADMIN_URL . '?pf=relatedEntries/js/filter-controls.js');
            $starting_script .= dcPage::jsPageTabs(dcCore::app()->admin->default_tab);
            $starting_script .= dcPage::jsConfirmClose('config-form');
            $starting_script .= '<script>' . "\n" .
            '//<![CDATA[' . "\n" .
            dcPage::jsVar('dotclear.msg.show_filters', dcCore::app()->admin->show_filters ? 'true' : 'false') . "\n" .
            dcPage::jsVar('dotclear.msg.filter_posts_list', $form_filter_title) . "\n" .
            dcPage::jsVar('dotclear.msg.cancel_the_filter', __('Cancel filters and display options')) . "\n" .
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

                echo
                '<form action="' . dcCore::app()->admin->getPageURL() . '" method="get" id="filters-form">' .
                '<h3 class="out-of-screen-if-js">' . __('Filter posts list') . '</h3>' .
                '<div class="table">' .
                '<div class="cell">' .
                '<h4>' . __('Filters') . '</h4>' .
                '<p><label for="user_id" class="ib">' . __('Author:') . '</label> ' .
                    form::combo('user_id', dcCore::app()->admin->users_combo, dcCore::app()->admin->user_id) . '</p>' .
                    '<p><label for="cat_id" class="ib">' . __('Category:') . '</label> ' .
                    form::combo('cat_id', dcCore::app()->admin->categories_combo, dcCore::app()->admin->cat_id) . '</p>' .
                    '<p><label for="status" class="ib">' . __('Status:') . '</label> ' .
                    form::combo('status', dcCore::app()->admin->status_combo, dcCore::app()->admin->status) . '</p> ' .
                '</div>' .

                '<div class="cell filters-sibling-cell">' .
                    '<p><label for="selected" class="ib">' . __('Selected:') . '</label> ' .
                    form::combo('selected', dcCore::app()->admin->selected_combo, dcCore::app()->admin->selected) . '</p>' .
                    '<p><label for="month" class="ib">' . __('Month:') . '</label> ' .
                    form::combo('month', dcCore::app()->admin->dt_m_combo, dcCore::app()->admin->month) . '</p>' .
                    '<p><label for="lang" class="ib">' . __('Lang:') . '</label> ' .
                    form::combo('lang', dcCore::app()->admin->lang_combo, dcCore::app()->admin->lang) . '</p> ' .
                '</div>' .

                '<div class="cell filters-options">' .
                    '<h4>' . __('Display options') . '</h4>' .
                    '<p><label for="sortby" class="ib">' . __('Order by:') . '</label> ' .
                    form::combo('sortby', dcCore::app()->admin->sortby_combo, dcCore::app()->admin->sortby) . '</p>' .
                    '<p><label for="order" class="ib">' . __('Sort:') . '</label> ' .
                    form::combo('order', dcCore::app()->admin->order_combo, dcCore::app()->admin->order) . '</p>' .
                    '<p><span class="label ib">' . __('Show') . '</span> <label for="nb" class="classic">' .
                    form::field('nb', 3, 3, dcCore::app()->admin->nb_per_page) . ' ' .
                    __('entries per page') . '</label></p>' .
                '</div>' .
                '</div>' .

                '<p><input type="submit" value="' . __('Apply filters and display options') . '" />' .
                    '<br class="clear" /></p>' . //Opera sucks
                '<p>' . form::hidden(['relatedEntries_filters'], 'relatedEntries') .
                '<input type="hidden" name="p" value="relatedEntries" />' .
                '<input type="hidden" name="addlinks" value="1" />' .
                form::hidden(['id'], dcCore::app()->admin->id) .
                dcCore::app()->formNonce() .
                '</p>' .
                '</form>';

                // Show posts
                if (!isset(dcCore::app()->admin->posts_list) || empty(dcCore::app()->admin->posts_list)) {
                    echo '<p><strong>' . __('No related posts') . '</strong></p>';
                } else {
                    dcCore::app()->admin->posts_list->display(
                        dcCore::app()->admin->page,
                        dcCore::app()->admin->nb_per_page,
                        '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="form-entries">' .

                        '%s' .

                        '<div class="two-cols">' .
                        '<p class="col checkboxes-helpers"></p>' .

                        '<p class="col right">' .
                        '<input type="submit" value="' . __('Add links to selected posts') . '" /> <a class="button reset" href="post.php?id=' . dcCore::app()->admin->id . '&upd=1">' . __('Cancel') . '</a></p>' .
                        '<p>' .
                        '<input type="hidden" name="p" value="relatedEntries" />' .
                        '<input type="hidden" name="addlinks" value="1" />' .
                        form::hidden(['id'], dcCore::app()->admin->id) .
                        dcCore::app()->formNonce() . '</p>' .
                        '</div>' .
                        '</form>'
                    );
                }
            }
            dcPage::helpBlock('relatedEntriesposts');
        } else {
            dcCore::app()->admin->posts      = null;
            dcCore::app()->admin->posts_list = null;

            // Get posts with related posts
            try {
                $params['no_content'] = true;
                $params['sql']        = 'AND P.post_id IN (SELECT META.post_id FROM ' . dcCore::app()->prefix . 'meta META WHERE META.post_id = P.post_id ' .
                        "AND META.meta_type = 'relatedEntries' ) ";
                dcCore::app()->admin->posts      = dcCore::app()->blog->getPosts($params);
                $counter                         = dcCore::app()->blog->getPosts($params, true);
                dcCore::app()->admin->posts_list = new adminPostList(dcCore::app()->admin->posts, $counter->f(0));
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
                dcPage::message(__('Configuration successfully saved'));
            } elseif (isset($_GET['upd']) && $_GET['upd'] == 2) {
                dcPage::message(__('Links have been successfully removed'));
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

            echo
                '<form action="' . dcCore::app()->admin->getPageURL() . '" method="get" id="filters-form">' .
                '<h3 class="out-of-screen-if-js">' . __('Filter posts list') . '</h3>' .
                '<div class="table">' .
                '<div class="cell">' .
                '<h4>' . __('Filters') . '</h4>' .
                '<p><label for="user_id" class="ib">' . __('Author:') . '</label> ' .
                    form::combo('user_id', dcCore::app()->admin->users_combo, dcCore::app()->admin->user_id) . '</p>' .
                    '<p><label for="cat_id" class="ib">' . __('Category:') . '</label> ' .
                    form::combo('cat_id', dcCore::app()->admin->categories_combo, dcCore::app()->admin->cat_id) . '</p>' .
                    '<p><label for="status" class="ib">' . __('Status:') . '</label> ' .
                    form::combo('status', dcCore::app()->admin->status_combo, dcCore::app()->admin->status) . '</p> ' .
                '</div>' .

                '<div class="cell filters-sibling-cell">' .
                    '<p><label for="selected" class="ib">' . __('Selected:') . '</label> ' .
                    form::combo('selected', dcCore::app()->admin->selected_combo, dcCore::app()->admin->selected) . '</p>' .
                    '<p><label for="month" class="ib">' . __('Month:') . '</label> ' .
                    form::combo('month', dcCore::app()->admin->dt_m_combo, dcCore::app()->admin->month) . '</p>' .
                    '<p><label for="lang" class="ib">' . __('Lang:') . '</label> ' .
                    form::combo('lang', dcCore::app()->admin->lang_combo, dcCore::app()->admin->lang) . '</p> ' .
                '</div>' .

                '<div class="cell filters-options">' .
                    '<h4>' . __('Display options') . '</h4>' .
                    '<p><label for="sortby" class="ib">' . __('Order by:') . '</label> ' .
                    form::combo('sortby', dcCore::app()->admin->sortby_combo, dcCore::app()->admin->sortby) . '</p>' .
                    '<p><label for="order" class="ib">' . __('Sort:') . '</label> ' .
                    form::combo('order', dcCore::app()->admin->order_combo, dcCore::app()->admin->order) . '</p>' .
                    '<p><span class="label ib">' . __('Show') . '</span> <label for="nb" class="classic">' .
                    form::field('nb', 3, 3, dcCore::app()->admin->nb_per_page) . ' ' .
                    __('entries per page') . '</label></p>' .
                '</div>' .
                '</div>' .
                '<p>' . dcCore::app()->formNonce() . '</p>' .
                '<p><input type="submit" value="' . __('Apply filters and display options') . '" />' .
                    '<br class="clear" /></p>' . //Opera sucks
                '<p>' . form::hidden(['relatedEntries_filters_config'], 'relatedEntries') .
                '<input type="hidden" name="p" value="relatedEntries" />' .
                form::hidden(['id'], dcCore::app()->admin->id) .
                form::hidden(['tab'], 'postslist') .
                '</p>' .
                '</form>';

            if (!isset(dcCore::app()->admin->posts_list) || empty(dcCore::app()->admin->posts_list)) {
                echo '<p><strong>' . __('No related posts') . '</strong></p>';
            } else {
                // Show posts
                dcCore::app()->admin->posts_list->display(
                    dcCore::app()->admin->page,
                    dcCore::app()->admin->nb_per_page,
                    '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="form-entries">' .

                    '%s' .

                    '<div class="two-cols">' .
                    '<p class="col checkboxes-helpers"></p>' .

                    '<p class="col right">' .
                    '<input type="submit" class="delete" value="' . __('Remove all links from selected posts') . '" /></p>' .
                    '<p>' .
                    '<input type="hidden" name="p" value="relatedEntries" />' .
                    form::hidden(['tab'], 'postslist') .
                    form::hidden(['id'], dcCore::app()->admin->id) .
                    dcCore::app()->formNonce() . '</p>' .
                    '</div>' .
                    '</form>'
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
