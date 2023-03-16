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

use adminGenericListV2;
use dcBlog;
use dcCore;
use dt;
use html;
use dcPager;
use form;
use dcUtils;
use adminPostList;

class BackendList extends adminGenericListV2
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
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

        // Get posts without current

        if (isset($_GET['id'])) {
            try {
                $id                        = $_GET['id'];
                $params['no_content']      = true;
                $params['exclude_post_id'] = $id;
                $posts                     = dcCore::app()->blog->getPosts($params);
                $counter                   = dcCore::app()->blog->getPosts($params, true);
                $post_list                 = new adminPostList($posts, $counter->f(0));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

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
        dcCore::app()->admin->nb_per_page      = $nb_per_page;
        dcCore::app()->admin->id               = $id;
        /*
         * Posts list
         */
        dcCore::app()->admin->post_list   = $post_list;
        dcCore::app()->admin->page        = $page;
        dcCore::app()->admin->nb_per_page = $nb_per_page;

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
                __('Related posts')                         => dcCore::app()->admin->getPageURL(),
                $page_title                                 => '',
            ]
        ) .
            '<p class="clear">' . __('Select posts related to entry:') . ' <a href="' . dcCore::app()->getPostAdminURL($rs->post_type, $rs->post_id) . '">' . $post_title . '</a>';

        
        
        dcPage::helpBlock('relatedEntries');
        echo
        '</body>' .
        '</html>';
    }
}
