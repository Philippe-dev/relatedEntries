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

if (!isset(dcCore::app()->resources['help']['relatedEntries'])) {
    dcCore::app()->resources['help']['relatedEntries'] = dirname(__FILE__).'/help/config_help.html';
}
if (!isset(dcCore::app()->resources['help']['relatedEntriesposts'])) {
    dcCore::app()->resources['help']['relatedEntriesposts'] = dirname(__FILE__).'/help/posts_help.html';
}
if (!isset(dcCore::app()->resources['help']['relatedEntries_post'])) {
    dcCore::app()->resources['help']['relatedEntries_post'] = dirname(__FILE__).'/help/relatedEntries_post.html';
}
