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
use dcMeta;
use dt;

class BackendBehaviors
{
    public static function adminColumnsLists($cols)
    {
        $cols['posts'][1]['links']  = [true, __('Links')];
    }

    private static function adminEntryListHeader($core, $rs, $cols)
    {
        $cols['links']  = '<th scope="col">' . __('Links') . '</th>';
    }

    public static function adminPostListHeader($rs, $cols)
    {
        self::adminEntryListHeader(dcCore::app(), $rs, $cols);
    }

    public static function adminPagesListHeader($rs, $cols)
    {
        self::adminEntryListHeader(dcCore::app(), $rs, $cols);
    }

    public static function adminEntryListValue($core, $rs, $cols)
    {
        $cols['links']  = '<td class="nowrap">' . $rs->post_id . '</td>';
    }

    public static function adminPostListValue($rs, $cols)
    {
        self::adminEntryListValue(dcCore::app(), $rs, $cols);
    }

    public static function adminPagesListValue($rs, $cols)
    {
        self::adminEntryListValue(dcCore::app(), $rs, $cols);
    }

    public static function adminPostsSortbyCombo($container)
    {
        $container[1][__('ID')] = 'post_id';
    }
}
