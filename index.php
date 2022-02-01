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

if (isset($_GET['tab']) || isset($_POST['tab'])) {
    require_once(dirname(__FILE__).'/config.php');
} elseif (isset($_GET['id']) || isset($_POST['id']) || isset($_GET['relatedEntries_filters'])) {
    require_once(dirname(__FILE__).'/posts.php');
} elseif ((isset($_GET['id']) || isset($_POST['id'])) && isset($_GET['relatedEntries_filters_config'])) {
    require_once(dirname(__FILE__).'/config.php');
} else {
    require_once(dirname(__FILE__).'/config.php');
}
