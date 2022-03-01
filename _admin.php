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

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$_menu['Blog']->addItem(
    __('Related posts'),
    $core->adminurl->get('admin.plugin.relatedEntries'),
    [dcPage::getPF('relatedEntries/icon.svg')],
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.relatedEntries')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('usage,contentadmin', $core->blog->id)
);

$core->addBehavior(
    'adminDashboardFavorites',
    function ($core, $favs) {
        $favs->register('relatedEntries', [
            'title' => __('Related posts'),
            'url' => $core->adminurl->get('admin.plugin.relatedEntries'),
            'small-icon' => [dcPage::getPF('relatedEntries/icon.svg')],
            'large-icon' => [dcPage::getPF('relatedEntries/icon.svg')],
            'permissions' => 'usage,contentadmin',
        ]);
    }
);

$core->addBehavior('adminPageHelpBlock', ['relatedEntriesBehaviors', 'adminPageHelpBlock']);

class relatedEntriesBehaviors
{
    public static function adminPageHelpBlock($blocks)
    {
        $found = false;
        foreach ($blocks as $block) {
            if ($block == 'core_post') {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return null;
        }
        $blocks[] = 'relatedEntries_post';
    }
}

require dirname(__FILE__) . '/_widget.php';

$core->addBehavior('adminPostHeaders', ['relatedEntriesPostBehaviors', 'postHeaders']);
$core->addBehavior('adminPostForm', ['relatedEntriesPostBehaviors', 'adminPostForm']);

$__autoload['adminRelatedPostMiniList'] = dirname(__FILE__) . '/inc/class.relatedEntries.minilist.php';

if (isset($_GET['id']) && isset($_GET['r_id'])) {
    try {
        $meta = &$GLOBALS['core']->meta;
        $id = $_GET['id'];
        $r_ids = $_GET['r_id'];

        foreach ($meta->splitMetaValues($r_ids) as $tag) {
            $meta->delPostMeta($id, 'relatedEntries', $tag);
            $meta->delPostMeta($tag, 'relatedEntries', $id);
        }

        http::redirect(DC_ADMIN_URL . 'post.php?id=' . $id . '&del=1#relatedEntries-area');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

class relatedEntriesPostBehaviors
{
    public static function postHeaders()
    {
        global $core;
        $s = &$core->blog->settings->relatedEntries;
        if (!$s->relatedEntries_enabled) {
            return;
        }

        return
        '<script>' . "\n" .
        '$(document).ready(function() {' . "\n" .
            '$(\'#relatedEntries-area label\').toggleWithLegend($(\'#relatedEntries-list\'), {' . "\n" .
                'legend_click: true,' . "\n" .
                'cookie: \'dcx_relatedEntries_detail\'' . "\n" .

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
        '<style type="text/css">' . "\n" .
        'a.links-remove {' . "\n" .
        'color : #900;' . "\n" .
        '}' . "\n" .
        '</style>';
    }

    public static function adminPostForm($post)
    {
        global $core;
        $s = $core->blog->settings->relatedEntries;
        $p_url = 'plugin.php?p=' . basename(dirname(__FILE__));
        $postTypes = ['post'];

        if (!$s->relatedEntries_enabled) {
            return;
        }
        if (is_null($post) || !in_array($post->post_type, $postTypes)) {
            return;
        }

        $id = $post->post_id;
        $type = $post->post_type;
        $meta = &$GLOBALS['core']->meta;
        $meta_rs = $meta->getMetaStr($post->post_meta, 'relatedEntries');

        if (!$meta_rs) {
            echo
                '<div class="area" id="relatedEntries-area">' .
                '<p class="smart-title">' . __('Related posts') . '</p>' .
                '<div id="relatedEntries-list" >' .
                '<p>' . __('No related posts') . '</p>' .
                '<p><a href="' . $p_url . '&amp;id=' . $id . '">' . __('Add links to related posts') . '</a></p>' .
                '</div>' .
                '</div>';
        } else {
            echo
                '<div class="area" id="relatedEntries-area">' .
                '<p class="smart-title">' . __('Links to related posts') . '</p>' .
                '<div id="relatedEntries-list" >';

            // Get related posts
            try {
                $params['post_id'] = $meta->splitMetaValues($meta_rs);
                $params['no_content'] = true;
                $params['post_type'] = ['post'];
                $posts = $core->blog->getPosts($params);
                $counter = $core->blog->getPosts($params, true);
                $post_list = new adminRelatedPostMiniList($core, $posts, $counter->f(0));
            } catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
            $page = '1';
            $nb_per_page = '50';

            echo
                '<div id="form-entries">' .
                $post_list->display($page, $nb_per_page) .
                '</div>';
            echo

            '<p class="two-boxes"><a href="' . $p_url . '&amp;id=' . $id . '"><strong>' . __('Add links to related posts') . '</strong></a></p>' .
            '<p class="two-boxes right"><a class="links-remove delete" href="' . $p_url . '&amp;id=' . $id . '&amp;r_id=' . $meta_rs . '">' . __('Remove all links to related posts') . '</a></p>' .

            form::hidden(['relatedEntries'], $meta_rs) .
            '</div>' .
            '</div>';
        }
    }
}
