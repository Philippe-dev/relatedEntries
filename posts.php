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

dcPage::check('usage,contentadmin');

global $core;

$p_url	= 'plugin.php?p='.basename(dirname(__FILE__));

$s =& $core->blog->settings->relatedEntries;

$page_title = __('Add related posts links to entry');

# Getting categories
try {
    $categories = $core->blog->getCategories(array('post_type'=>'post'));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# Getting authors
try {
    $users = $core->blog->getPostsUsers();
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# Getting dates
try {
    $dates = $core->blog->getDates(array('type'=>'month'));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# Getting langs
try {
    $langs = $core->blog->getLangs();
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# Creating filter combo boxes
if (!$core->error->flag()) {
    # Filter form we'll put in html_block
    $users_combo = $categories_combo = array();
    $users_combo['-'] = $categories_combo['-'] = '';
    while ($users->fetch()) {
        $user_cn = dcUtils::getUserCN(
            $users->user_id,
            $users->user_name,
            $users->user_firstname,
            $users->user_displayname
        );
        
        if ($user_cn != $users->user_id) {
            $user_cn .= ' ('.$users->user_id.')';
        }
        
        $users_combo[$user_cn] = $users->user_id;
    }
    
    $categories_combo[__('None')] = 'NULL';
    while ($categories->fetch()) {
        $categories_combo[str_repeat('&nbsp;&nbsp;', $categories->level-1).($categories->level-1 == 0 ? '' : '&bull; ').
            html::escapeHTML($categories->cat_title).
            ' ('.$categories->nb_post.')'] = $categories->cat_id;
    }
    
    $status_combo = array(
    '-' => ''
    );
    foreach ($core->blog->getAllPostStatus() as $k => $v) {
        $status_combo[$v] = (string) $k;
    }
    $img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';
    
    $selected_combo = array(
    '-' => '',
    __('selected') => '1',
    __('not selected') => '0'
    );
    
    # Months array
    $dt_m_combo['-'] = '';
    while ($dates->fetch()) {
        $dt_m_combo[dt::str('%B %Y', $dates->ts())] = $dates->year().$dates->month();
    }
    
    $lang_combo['-'] = '';
    while ($langs->fetch()) {
        $lang_combo[$langs->post_lang] = $langs->post_lang;
    }
    
    $sortby_combo = array(
    __('Date') => 'post_dt',
    __('Title') => 'post_title',
    __('Category') => 'cat_title',
    __('Author') => 'user_id',
    __('Status') => 'post_status',
    __('Selected') => 'post_selected'
    );
    
    $order_combo = array(
    __('Descending') => 'desc',
    __('Ascending') => 'asc'
    );
}

/* Get posts
-------------------------------------------------------- */
$id = !empty($_GET['id']) ?	$_GET['id'] : '';
$user_id = !empty($_GET['user_id']) ?	$_GET['user_id'] : '';
$cat_id = !empty($_GET['cat_id']) ?	$_GET['cat_id'] : '';
$status = isset($_GET['status']) ?	$_GET['status'] : '';
$selected = isset($_GET['selected']) ?	$_GET['selected'] : '';
$month = !empty($_GET['month']) ?		$_GET['month'] : '';
$entries = !empty($_GET['entries']) ?		$_GET['entries'] : '';
$lang = !empty($_GET['lang']) ?		$_GET['lang'] : '';
$sortby = !empty($_GET['sortby']) ?	$_GET['sortby'] : 'post_dt';
$order = !empty($_GET['order']) ?		$_GET['order'] : 'desc';

$show_filters = false;

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page =  30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
    if ($nb_per_page != $_GET['nb']) {
        $show_filters = true;
    }
    $nb_per_page = (integer) $_GET['nb'];
}

$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;

# - User filter
if ($user_id !== '' && in_array($user_id, $users_combo)) {
    $params['user_id'] = $user_id;
    $show_filters = true;
} else {
    $user_id='';
}

# - Categories filter
if ($cat_id !== '' && in_array($cat_id, $categories_combo)) {
    $params['cat_id'] = $cat_id;
    $show_filters = true;
} else {
    $cat_id='';
}

# - Status filter
if ($status !== '' && in_array($status, $status_combo)) {
    $params['post_status'] = $status;
    $show_filters = true;
} else {
    $status='';
}

# - Selected filter
if ($selected !== '' && in_array($selected, $selected_combo)) {
    $params['post_selected'] = $selected;
    $show_filters = true;
} else {
    $selected='';
}

# - Month filter
if ($month !== '' && in_array($month, $dt_m_combo)) {
    $params['post_month'] = substr($month, 4, 2);
    $params['post_year'] = substr($month, 0, 4);
    $show_filters = true;
} else {
    $month='';
}

# - Lang filter
if ($lang !== '' && in_array($lang, $lang_combo)) {
    $params['post_lang'] = $lang;
    $show_filters = true;
} else {
    $lang='';
}

# - Sortby and order filter
if ($sortby !== '' && in_array($sortby, $sortby_combo)) {
    if ($order !== '' && in_array($order, $order_combo)) {
        $params['order'] = $sortby.' '.$order;
    } else {
        $order='desc';
    }
    
    if ($sortby != 'post_dt' || $order != 'desc') {
        $show_filters = true;
    }
} else {
    $sortby='post_dt';
    $order='desc';
}

# Get posts without current

if (isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        $params['no_content'] = true;
        $params['exclude_post_id'] = $id;
        $posts = $core->blog->getPosts($params);
        $counter = $core->blog->getPosts($params, true);
        $post_list = new adminPostList($core, $posts, $counter->f(0));
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Save relatedEntries

if (isset($_POST['entries'])) {
    try {
        $entries = implode(', ', $_POST['entries']);
        $id = $_POST['id'];
    
        $meta = $core->meta;
    
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
            
        http::redirect(DC_ADMIN_URL.'post.php?id='.$id.'&add=1#relatedEntries-area');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

/* DISPLAY
-------------------------------------------------------- */
?>
<html>
	<head>
		<title><?php echo $page_title; ?></title>
		<?php
        $form_filter_title = __('Show filters and display options');
        $starting_script  = dcPage::jsLoad('js/_posts_list.js');
        $starting_script .= dcPage::jsLoad(DC_ADMIN_URL.'?pf=relatedEntries/js/posts-filter-controls.js');
        $starting_script .=
        '<script type="text/javascript">'."\n".
        "//<![CDATA["."\n".
        dcPage::jsVar('dotclear.msg.show_filters', $show_filters ? 'true':'false')."\n".
        dcPage::jsVar('dotclear.msg.filter_posts_list', $form_filter_title)."\n".
        dcPage::jsVar('dotclear.msg.cancel_the_filter', __('Cancel filters and display options'))."\n".
        dcPage::jsVar('id', $id)."\n".
        "//]]>".
        "</script>";
        echo $starting_script;
         ?>
	</head>
	<body>
<?php

if (!$core->error->flag()) {
    $id = (int) $_GET['id'];
    $my_params['post_id'] = $id;
    $my_params['no_content'] = true;
    $my_params['post_type'] = array('post');
    
    $rs = $core->blog->getPosts($my_params);
    $post_title = $rs->post_title;
    $post_status = $rs->post_status;
    
    echo dcPage::breadcrumb(
        array(
            html::escapeHTML($core->blog->name) => '',
            __('Related posts') => $p_url,
            $page_title => ''
        )
    ).
        '<p class="clear">'.__('Select posts related to entry:').' <a href="'.$core->getPostAdminURL($rs->post_type, $rs->post_id).'">'.$post_title.'</a>';
    
    if ($id) {
        switch ($post_status) {
        case 1:
            $img_status = sprintf($img_status_pattern, __('published'), 'check-on.png');
            break;
        case 0:
            $img_status = sprintf($img_status_pattern, __('unpublished'), 'check-off.png');
            break;
        case -1:
            $img_status = sprintf($img_status_pattern, __('scheduled'), 'scheduled.png');
            break;
        case -2:
            $img_status = sprintf($img_status_pattern, __('pending'), 'check-wrn.png');
            break;
        default:
            $img_status = '';
    }
        echo '&nbsp;&nbsp;&nbsp;'.$img_status;
    }
    
    echo '</p>';
    
    
    
    echo
    '<form action="'.$p_url.'" method="get" id="filters-form">'.
    '<h3 class="out-of-screen-if-js">'.__('Filter posts list').'</h3>'.
    '<div class="table">'.
    '<div class="cell">'.
    '<h4>'.__('Filters').'</h4>'.
    '<p><label for="user_id" class="ib">'.__('Author:').'</label> '.
        form::combo('user_id', $users_combo, $user_id).'</p>'.
        '<p><label for="cat_id" class="ib">'.__('Category:').'</label> '.
        form::combo('cat_id', $categories_combo, $cat_id).'</p>'.
        '<p><label for="status" class="ib">'.__('Status:').'</label> ' .
        form::combo('status', $status_combo, $status).'</p> '.
    '</div>'.
    
    '<div class="cell filters-sibling-cell">'.
        '<p><label for="selected" class="ib">'.__('Selected:').'</label> '.
        form::combo('selected', $selected_combo, $selected).'</p>'.
        '<p><label for="month" class="ib">'.__('Month:').'</label> '.
        form::combo('month', $dt_m_combo, $month).'</p>'.
        '<p><label for="lang" class="ib">'.__('Lang:').'</label> '.
        form::combo('lang', $lang_combo, $lang).'</p> '.
    '</div>'.
    
    '<div class="cell filters-options">'.
        '<h4>'.__('Display options').'</h4>'.
        '<p><label for="sortby" class="ib">'.__('Order by:').'</label> '.
        form::combo('sortby', $sortby_combo, $sortby).'</p>'.
        '<p><label for="order" class="ib">'.__('Sort:').'</label> '.
        form::combo('order', $order_combo, $order).'</p>'.
        '<p><span class="label ib">'.__('Show').'</span> <label for="nb" class="classic">'.
        form::field('nb', 3, 3, $nb_per_page).' '.
        __('entries per page').'</label></p>'.
    '</div>'.
    '</div>'.
    
    '<p><input type="submit" value="'.__('Apply filters and display options').'" />'.
        '<br class="clear" /></p>'. //Opera sucks
    '<p>'.form::hidden(array('relatedEntries_filters'), 'relatedEntries').
    '<input type="hidden" name="p" value="relatedEntries" />'.
    form::hidden(array('id'), $id).
    $core->formNonce().
    '</p>'.
    '</form>';
    
    # Show posts
    $post_list->display(
        $page,
        $nb_per_page,
        '<form action="'.$p_url.'" method="post" id="form-entries">'.
    
    '%s'.
    
    '<div class="two-cols">'.
    '<p class="col checkboxes-helpers"></p>'.
    
    '<p class="col right">'.
    '<input type="submit" value="'.__('Add links to selected posts').'" /> <a class="button reset" href="post.php?id='.$id.'#relatedEntries-area">'.__('Cancel').'</a></p>'.
    '<p>'.
    '<input type="hidden" name="p" value="relatedEntries" />'.
    form::hidden(array('id'), $id).
    $core->formNonce().'</p>'.
    '</div>'.
    '</form>',
        $show_filters
    );
}
dcPage::helpBlock('relatedEntriesposts');
?>
	</body>
</html>
