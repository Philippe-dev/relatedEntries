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

if (!$core->auth->check('admin', $core->blog->id)) {
    return;
}

//Settings
$s = &$core->blog->settings->relatedEntries;

// Init var
$p_url = 'plugin.php?p=' . basename(dirname(__FILE__));

$default_tab = isset($_GET['tab']) ? $_GET['tab'] : 'parameters';

// Saving configurations
if (isset($_POST['save'])) {
    $s->put('relatedEntries_enabled', !empty($_POST['relatedEntries_enabled']));
    $s->put('relatedEntries_title', html::escapeHTML($_POST['relatedEntries_title']));
    $s->put('relatedEntries_beforePost', !empty($_POST['relatedEntries_beforePost']));
    $s->put('relatedEntries_afterPost', !empty($_POST['relatedEntries_afterPost']));
    $s->put('relatedEntries_images', !empty($_POST['relatedEntries_images']));

    $opts = [
        'size' => !empty($_POST['size']) ? $_POST['size'] : 't',
        'html_tag' => !empty($_POST['html_tag']) ? $_POST['html_tag'] : 'div',
        'link' => !empty($_POST['link']) ? $_POST['link'] : 'entry',
        'exif' => 0,
        'legend' => !empty($_POST['legend']) ? $_POST['legend'] : 'none',
        'bubble' => !empty($_POST['bubble']) ? $_POST['bubble'] : 'image',
        'from' => !empty($_POST['from']) ? $_POST['from'] : 'full',
        'start' => !empty($_POST['start']) ? $_POST['start'] : 1,
        'length' => !empty($_POST['length']) ? $_POST['length'] : 1,
        'class' => !empty($_POST['class']) ? $_POST['class'] : '',
        'alt' => !empty($_POST['alt']) ? $_POST['alt'] : 'inherit',
        'img_dim' => !empty($_POST['img_dim']) ? $_POST['img_dim'] : 0,
    ];

    $s->put('relatedEntries_images_options', serialize($opts));

    $core->blog->triggerBlog();
    http::redirect($p_url . '&upd=1');
}
//Remove related posts links

if (isset($_POST['entries'])) {
    $meta = &$GLOBALS['core']->meta;

    try {
        $tags = [];

        foreach ($_POST['entries'] as $id) {
            // Get tags for post
            $post_meta = $meta->getMetadata([
                'meta_type' => 'relatedEntries',
                'post_id' => $id]);
            $pm = [];
            while ($post_meta->fetch()) {
                $pm[] = $post_meta->meta_id;
            }
            foreach ($pm as $tag) {
                $meta->delPostMeta($id, 'relatedEntries', $tag);
                $meta->delPostMeta($tag, 'relatedEntries', $id);
            }
        }

        http::redirect($p_url . '&upd=2&tab=postslist');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

// Image size combo
$img_size_combo = [];
$media = new dcMedia($core);

$img_size_combo[__('square')] = 'sq';
$img_size_combo[__('thumbnail')] = 't';
$img_size_combo[__('small')] = 's';
$img_size_combo[__('medium')] = 'm';
$img_size_combo[__('original')] = 'o';
foreach ($media->thumb_sizes as $code => $size) {
    $img_size_combo[__($size[2])] = $code;
}

// Html tag combo
$html_tag_combo = [
    __('div') => 'div',
    __('li') => 'li',
    __('no tag') => 'none'
];

// Link combo
$link_combo = [
    __('related posts') => 'entry',
    __('original images') => 'image',
    __('no link') => 'none'
];

// Legend combo
$legend_combo = [
    __('entry title') => 'entry',
    __('image title') => 'image',
    __('no legend') => 'none'
];

// Bubble combo
$bubble_combo = [
    __('entry title') => 'entry',
    __('image title') => 'image',
    __('no bubble') => 'none'
];

// From combo
$from_combo = [
    __('post excerpt') => 'excerpt',
    __('post content') => 'content',
    __('full post') => 'full'
];

// Alt combo
$alt_combo = [
    __('image title') => 'inherit',
    __('no alt') => 'none'
];

// Getting categories
try {
    $categories = $core->blog->getCategories(['post_type' => 'post']);
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

// Getting authors
try {
    $users = $core->blog->getPostsUsers();
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

// Getting dates
try {
    $dates = $core->blog->getDates(['type' => 'month']);
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

// Getting langs
try {
    $langs = $core->blog->getLangs();
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

// Creating filter combo boxes
if (!$core->error->flag()) {
    // Filter form we'll put in html_block
    $users_combo = $categories_combo = [];
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
        '-' => ''
    ];
    foreach ($core->blog->getAllPostStatus() as $k => $v) {
        $status_combo[$v] = (string) $k;
    }

    $selected_combo = [
        '-' => '',
        __('selected') => '1',
        __('not selected') => '0'
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
        __('Date') => 'post_dt',
        __('Title') => 'post_title',
        __('Category') => 'cat_title',
        __('Author') => 'user_id',
        __('Status') => 'post_status',
        __('Selected') => 'post_selected'
    ];

    $order_combo = [
        __('Descending') => 'desc',
        __('Ascending') => 'asc'
    ];
}

/* Get posts
-------------------------------------------------------- */
$id = !empty($_GET['id']) ? $_GET['id'] : '';
$user_id = !empty($_GET['user_id']) ? $_GET['user_id'] : '';
$cat_id = !empty($_GET['cat_id']) ? $_GET['cat_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$selected = isset($_GET['selected']) ? $_GET['selected'] : '';
$month = !empty($_GET['month']) ? $_GET['month'] : '';
$entries = !empty($_GET['entries']) ? $_GET['entries'] : '';
$lang = !empty($_GET['lang']) ? $_GET['lang'] : '';
$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'post_dt';
$order = !empty($_GET['order']) ? $_GET['order'] : 'desc';

$show_filters = false;

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page = 30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
    if ($nb_per_page != $_GET['nb']) {
        $show_filters = true;
    }
    $nb_per_page = (integer) $_GET['nb'];
}

$params['limit'] = [(($page - 1) * $nb_per_page), $nb_per_page];
$params['no_content'] = true;

// - User filter
if ($user_id !== '' && in_array($user_id, $users_combo)) {
    $params['user_id'] = $user_id;
    $show_filters = true;
} else {
    $user_id = '';
}

// - Categories filter
if ($cat_id !== '' && in_array($cat_id, $categories_combo)) {
    $params['cat_id'] = $cat_id;
    $show_filters = true;
} else {
    $cat_id = '';
}

// - Status filter
if ($status !== '' && in_array($status, $status_combo)) {
    $params['post_status'] = $status;
    $show_filters = true;
} else {
    $status = '';
}

// - Selected filter
if ($selected !== '' && in_array($selected, $selected_combo)) {
    $params['post_selected'] = $selected;
    $show_filters = true;
} else {
    $selected = '';
}

// - Month filter
if ($month !== '' && in_array($month, $dt_m_combo)) {
    $params['post_month'] = substr($month, 4, 2);
    $params['post_year'] = substr($month, 0, 4);
    $show_filters = true;
} else {
    $month = '';
}

// - Lang filter
if ($lang !== '' && in_array($lang, $lang_combo)) {
    $params['post_lang'] = $lang;
    $show_filters = true;
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
    $order = 'desc';
}

// Get posts with related posts
try {
    $params['no_content'] = true;
    $params['sql'] = 'AND P.post_id IN (SELECT META.post_id FROM ' . $core->prefix . 'meta META WHERE META.post_id = P.post_id ' .
            "AND META.meta_type = 'relatedEntries' ) ";
    $posts = $core->blog->getPosts($params);
    $counter = $core->blog->getPosts($params, true);
    $post_list = new adminPostList($core, $posts, $counter->f(0));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */

// Baseline

$page_title = __('Related posts');

?>

<html>
<head>
	<title><?php echo(__('Related posts')); ?></title>
	
	<?php
    $form_filter_title = __('Show filters and display options');
    $starting_script = dcPage::jsLoad('js/_posts_list.js');
    $starting_script .= dcPage::jsLoad(DC_ADMIN_URL . '?pf=relatedEntries/js/filter-controls.js');
    $starting_script .= dcPage::jsPageTabs($default_tab);
    $starting_script .= dcPage::jsConfirmClose('config-form');
    $starting_script .=
    '<script>' . "\n" .
    '//<![CDATA[' . "\n" .
    dcPage::jsVar('dotclear.msg.show_filters', $show_filters ? 'true' : 'false') . "\n" .
    dcPage::jsVar('dotclear.msg.filter_posts_list', $form_filter_title) . "\n" .
    dcPage::jsVar('dotclear.msg.cancel_the_filter', __('Cancel filters and display options')) . "\n" .
    '//]]>' .
    '</script>';
    echo $starting_script;
     ?>
</head>
<body>

<?php

echo dcPage::breadcrumb(
    [
        html::escapeHTML($core->blog->name) => '',
        '<span class="page-title">' . $page_title . '</span>' => ''
    ]
);

// Display messages

if (isset($_GET['upd']) && $_GET['upd'] == 1) {
    dcPage::success(__('Configuration successfully saved'));
} elseif (isset($_GET['upd']) && $_GET['upd'] == 2) {
    dcPage::success(__('Links have been successfully removed'));
}

$as = unserialize($s->relatedEntries_images_options);

//Parameters tab

echo
'<div class="multi-part" id="parameters" title="' . __('Parameters') . '">' .
'<form action="' . $p_url . '" method="post" id="config-form">' .
'<div class="fieldset"><h3>' . __('Activation') . '</h3>' .
    '<p><label class="classic" for="relatedEntries_enabled">' .
    form::checkbox('relatedEntries_enabled', '1', $s->relatedEntries_enabled) .
    __('Enable related posts on this blog') . '</label></p>' .
'</div>' .
'<div class="fieldset"><h3>' . __('Display options') . '</h3>' .
    '<p class="field"><label class="maximal" for="relatedEntries_title">' . __('Block title:') . '&nbsp;' .
    form::field('relatedEntries_title', 40, 255, html::escapeHTML($s->relatedEntries_title)) .
    '</label></p>' .
    '<p><label class="classic" for="relatedEntries_beforePost">' .
    form::checkbox('relatedEntries_beforePost', '1', $s->relatedEntries_beforePost) .
    __('Display block before post content') . '</label></p>' .
    '<p><label class="classic" for="relatedEntries_afterPost">' .
    form::checkbox('relatedEntries_afterPost', '1', $s->relatedEntries_afterPost) .
    __('Display block after post content') . '</label></p>' .
    '<p class="form-note info clear">' . __('Uncheck both boxes to use only the presentation widget.') . '</p>' .
'</div>' .
'<div class="fieldset"><h3>' . __('Images extracting options') . '</h3>';

if ($core->plugins->moduleExists('listImages')) {
    echo
    '<p><label class="classic" for="relatedEntries_images">' .
    form::checkbox('relatedEntries_images', '1', $s->relatedEntries_images) .
    __('Extract images from related posts') . '</label></p>' .

    '<div class="two-boxes odd">' .

    '<p><label for="from">' . __('Images origin:') . '</label>' .
    form::combo(
        'from',
        $from_combo,
        ($as['from'] != '' ? $as['from'] : 'image')
    ) .
    '</p>' .

    '<p><label for="size">' . __('Image size:') . '</label>' .
    form::combo(
        'size',
        $img_size_combo,
        ($as['size'] != '' ? $as['size'] : 't')
    ) .
    '</p>' .

    '<p><label for="img_dim">' .
    form::checkbox('img_dim', '1', $as['img_dim']) .
    __('Include images dimensions') . '</label></p>' .

    '<p><label for="alt">' . __('Images alt attribute:') . '</label>' .
    form::combo(
        'alt',
        $alt_combo,
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
        $legend_combo,
        ($as['legend'] != '' ? $as['legend'] : 'none')
    ) .
    '</p>' .

    '<p><label for="html_tag">' . __('HTML tag around image:') . '</label>' .
    form::combo(
        'html_tag',
        $html_tag_combo,
        ($as['html_tag'] != '' ? $as['html_tag'] : 'div')
    ) .
    '</p>' .

    '<p><label for="class">' . __('CSS class on images:') . '</label>' .
        form::field('class', 10, 10, $as['class']) .
    '</p>' .

    '<p><label for="link">' . __('Links destination:') . '</label>' .
    form::combo(
        'link',
        $link_combo,
        ($as['link'] != '' ? $as['link'] : 'entry')
    ) .
    '</p>' .

    '<p><label for="bubble">' . __('Bubble:') . '</label>' .
    form::combo(
        'bubble',
        $bubble_combo,
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
'<p class="clear"><input type="submit" name="save" value="' . __('Save configuration') . '" />' . $core->formNonce() . '</p>' .
'</form>' .
'</div>' .

//Related posts list tab

'<div class="multi-part" id="postslist" title="' . __('Related posts list') . '">';

echo
    '<form action="' . $p_url . '" method="get" id="filters-form">' .
    '<h3 class="out-of-screen-if-js">' . __('Filter posts list') . '</h3>' .
    '<div class="table">' .
    '<div class="cell">' .
    '<h4>' . __('Filters') . '</h4>' .
    '<p><label for="user_id" class="ib">' . __('Author:') . '</label> ' .
        form::combo('user_id', $users_combo, $user_id) . '</p>' .
        '<p><label for="cat_id" class="ib">' . __('Category:') . '</label> ' .
        form::combo('cat_id', $categories_combo, $cat_id) . '</p>' .
        '<p><label for="status" class="ib">' . __('Status:') . '</label> ' .
        form::combo('status', $status_combo, $status) . '</p> ' .
    '</div>' .

    '<div class="cell filters-sibling-cell">' .
        '<p><label for="selected" class="ib">' . __('Selected:') . '</label> ' .
        form::combo('selected', $selected_combo, $selected) . '</p>' .
        '<p><label for="month" class="ib">' . __('Month:') . '</label> ' .
        form::combo('month', $dt_m_combo, $month) . '</p>' .
        '<p><label for="lang" class="ib">' . __('Lang:') . '</label> ' .
        form::combo('lang', $lang_combo, $lang) . '</p> ' .
    '</div>' .

    '<div class="cell filters-options">' .
        '<h4>' . __('Display options') . '</h4>' .
        '<p><label for="sortby" class="ib">' . __('Order by:') . '</label> ' .
        form::combo('sortby', $sortby_combo, $sortby) . '</p>' .
        '<p><label for="order" class="ib">' . __('Sort:') . '</label> ' .
        form::combo('order', $order_combo, $order) . '</p>' .
        '<p><span class="label ib">' . __('Show') . '</span> <label for="nb" class="classic">' .
        form::field('nb', 3, 3, $nb_per_page) . ' ' .
        __('entries per page') . '</label></p>' .
    '</div>' .
    '</div>' .
    '<p>' . $core->formNonce() . '</p>' .
    '<p><input type="submit" value="' . __('Apply filters and display options') . '" />' .
        '<br class="clear" /></p>' . //Opera sucks
    '<p>' . form::hidden(['relatedEntries_filters_config'], 'relatedEntries') .
    '<input type="hidden" name="p" value="relatedEntries" />' .
    form::hidden(['id'], $id) .
    form::hidden(['tab'], 'postslist') .
    '</p>' .
    '</form>';

    if (!isset($post_list) || empty($post_list)) {
        echo '<p><strong>' . __('No related posts') . '</strong></p>';
    } else {
        // Show posts
        $post_list->display(
            $page,
            $nb_per_page,
            '<form action="' . $p_url . '" method="post" id="form-entries">' .

        '%s' .

        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .

        '<p class="col right">' .
        '<input type="submit" class="delete" value="' . __('Remove all links from selected posts') . '" /></p>' .
        '<p>' .
        '<input type="hidden" name="p" value="relatedEntries" />' .
        form::hidden(['tab'], 'postslist') .
        form::hidden(['id'], 'fake') .
        $core->formNonce() . '</p>' .
        '</div>' .
        '</form>',
            $show_filters
        );
    }

echo
'</div>';

dcPage::helpBlock('relatedEntries');

?>

</body>
</html>