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
use dcBlog;
use dcNsProcess;
use dcPage;
use Exception;
use form;
use html;
use http;
use dcUtils;
use dt;

class Posts extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        $page_title = __('Add related posts links to entry');

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

            $img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';

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

        // Get posts without current

        if (isset($_GET['id'])) {
            try {
                $id                        = $_GET['id'];
                $params['no_content']      = true;
                $params['exclude_post_id'] = $id;
                $posts                     = dcCore::app()->blog->getPosts($params);
                $counter                   = dcCore::app()->blog->getPosts($params, true);
                $posts_list                = new PostsList($posts, $counter->f(0));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        $default_tab = $_GET['tab'] ?? 'parameters';

        /*
         * Admin page params.
         */
        dcCore::app()->admin->default_tab  = $default_tab;
        dcCore::app()->admin->show_filters = $show_filters;

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
        dcCore::app()->admin->posts              = $posts;
        dcCore::app()->admin->posts_list         = $posts_list;
        dcCore::app()->admin->page_title         = $page_title;
        dcCore::app()->admin->img_status_pattern = $img_status_pattern;
        dcCore::app()->admin->page               = $page;
        dcCore::app()->admin->nb_per_page        = $nb_per_page;

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

        // Save relatedEntries

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
                '<p class="clear">' . __('Select posts related to entry:') . ' <a href="' . dcCore::app()->getPostAdminURL($post_type, $post_id) . '">' . $post_title . '</a>';

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
            form::hidden(['id'], dcCore::app()->admin->id) .
            dcCore::app()->formNonce() .
            '</p>' .
            '</form>';

            // Show posts
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
                form::hidden(['id'], dcCore::app()->admin->id) .
                dcCore::app()->formNonce() . '</p>' .
                '</div>' .
                '</form>',
                dcCore::app()->admin->show_filters
            );
            
        }
        dcPage::helpBlock('relatedEntriesposts');
        echo
        '</body>' .
        '</html>';
    }
}
