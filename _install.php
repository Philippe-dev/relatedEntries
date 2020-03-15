<?php
/**
 * @brief Related Entries, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Philippe aka amalgame
 *
 * @copyright Philippe Hénaff philippe@dissitou.org
 * @copyright GPL-2.0 [https://www.gnu.org/licenses/gpl-2.0.html]
 */

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}
 
$m_version = $core->plugins->moduleInfo('relatedEntries', 'version');
 
$i_version = $core->getVersion('relatedEntries');
 
if (version_compare($i_version, $m_version, '>=')) {
    return;
}

# Settings
$core->blog->settings->addNamespace('relatedEntries');

$s =& $core->blog->settings->relatedEntries;

$s->put('relatedEntries_enabled', false, 'boolean', 'Enable related entries', false, true);
$s->put('relatedEntries_images', false, 'boolean', 'Display related entries links as images', false, true);
$s->put('relatedEntries_beforePost', false, 'boolean', 'Display related entries before post content', false, true);
$s->put('relatedEntries_afterPost', true, 'boolean', 'Display related entries after post content', false, true);
$s->put('relatedEntries_title', __('Related posts'), 'string', 'Related entries block title', false, true);

$opts = array(
    'size' => 't',
    'html_tag' => 'div',
    'link' => 'entry',
    'exif' => 0,
    'legend' => 'none',
    'bubble' => 'image',
    'from' => 'full',
    'start' => 1,
    'length' => 1,
    'class' => '',
    'alt' => 'inherit',
    'img_dim' => 0
);

$s->put('relatedEntries_images_options', serialize($opts), 'string', 'Related entries images options', false, true);

$core->setVersion('relatedEntries', $m_version);

return true;
