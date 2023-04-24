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
if (!isset(dcCore::app()->resources['help']['config'])) {
    dcCore::app()->resources['help']['config'] = dirname(__FILE__) . '/help/config_help.html';
}
if (!isset(dcCore::app()->resources['help']['posts'])) {
    dcCore::app()->resources['help']['posts'] = dirname(__FILE__) . '/help/posts_help.html';
}
if (!isset(dcCore::app()->resources['help']['post'])) {
    dcCore::app()->resources['help']['post'] = dirname(__FILE__) . '/help/post_help.html';
}
