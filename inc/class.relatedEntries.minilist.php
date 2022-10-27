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

// Posts related to current (admin.php)

class adminRelatedPostMiniList extends adminGenericList
{
    public function display($page, $nb_per_page, $enclose_block = '')
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . __('No entry') . '</strong></p>';
        } else {
            $pager            = new pager($page, $this->rs_count, $nb_per_page, 10);
            $pager->html_prev = $this->html_prev;
            $pager->html_next = $this->html_next;
            $pager->var_page  = 'page';

            $html_block = '<div class="table-outer clear">' .
            '<table><caption class="hidden">' . __('Entries list') . '</caption><tr>' .
            '<th class="first">' . __('Title') . '</th>' .
            '<th scope="col">' . __('Date') . '</th>' .
            '<th scope="col">' . __('Category') . '</th>' .
            '<th scope="col">' . __('Status') . '</th>' .
            '<th scope="col">' . __('Actions') . '</th>' .
            '</tr>%s</table></div>';

            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            $blocks = explode('%s', $html_block);

            echo $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->postLine();
            }

            echo $blocks[1];
        }
    }

    private function postLine()
    {
        $id = $_GET['id'];

        if (dcCore::app()->auth->check('categories', dcCore::app()->blog->id)) {
            $cat_link = '<a href="category.php?id=%s">%s</a>';
        } else {
            $cat_link = '%2$s';
        }

        if ($this->rs->cat_title) {
            $cat_title = sprintf(
                $cat_link,
                $this->rs->cat_id,
                html::escapeHTML($this->rs->cat_title)
            );
        } else {
            $cat_title = __('None');
        }

        $img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        switch ($rs->post_status) {
            case dcBlog::POST_PUBLISHED:
                $img_status = sprintf($img, __('published'), 'check-on.png');

                break;
            case dcBlog::POST_UNPUBLISHED:
                $img_status = sprintf($img, __('unpublished'), 'check-off.png');

                break;
            case dcBlog::POST_SCHEDULED:
                $img_status = sprintf($img, __('scheduled'), 'scheduled.png');

                break;
            case dcBlog::POST_PENDING:
                $img_status = sprintf($img, __('pending'), 'check-wrn.png');

                break;
        }

        $protected = '';
        if ($this->rs->post_password) {
            $protected = sprintf($img, __('protected'), 'locker.png');
        }

        $selected = '';
        if ($this->rs->post_selected) {
            $selected = sprintf($img, __('selected'), 'selected.png');
        }

        $attach   = '';
        $nb_media = $this->rs->countMedia();
        if ($nb_media > 0) {
            $attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
            $attach     = sprintf($img, sprintf($attach_str, $nb_media), 'attach.png');
        }

        $res = '<tr class="line' . ($this->rs->post_status != 1 ? ' offline' : '') . '"' .
        ' id="p' . $this->rs->post_id . '">';

        $res .= '<td class="maximal"><a href="' . dcCore::app()->getPostAdminURL($this->rs->post_type, $this->rs->post_id) . '">' .
        html::escapeHTML($this->rs->post_title) . '</a></td>' .
        '<td class="nowrap">' . dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt) . '</td>' .
        '<td class="nowrap">' . $cat_title . '</td>' .
        '<td class="nowrap status">' . $img_status . ' ' . $selected . ' ' . $protected . ' ' . $attach . '</td>' .
        '<td class="nowrap count"><a class="link-remove metaRemove" href="' . DC_ADMIN_URL . 'plugin.php?p=relatedEntries&amp;id=' . $id . '&amp;r_id=' . $this->rs->post_id . '" title="' . __('Delete this link') . '"><img src="images/trash.png" alt="supprimer" /></a></td>' .
        '</tr>';

        return $res;
    }
}
