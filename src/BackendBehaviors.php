<?php
/**
 * @brief Related Entries, a plugin for Dotclear 2
 *
 * @package    Dotclear
 * @subpackage Plugins
 *
 * @author Philippe aka amalgame
 *
 * @copyright GPL-2.0 [https://www.gnu.org/licenses/gpl-2.0.html]
 */

declare(strict_types=1);

namespace Dotclear\Plugin\relatedEntries;

use Dotclear\App;

class BackendBehaviors
{
    public static function adminColumnsLists($cols)
    {
        $cols['posts'][1]['Links'] = [true, __('Links')];
    }

    private static function adminEntryListHeader($rs, $cols)
    {
        $cols['Links'] = '<th scope="col">' . __('Links') . '</th>';
    }

    public static function adminPostListHeader($rs, $cols)
    {
        self::adminEntryListHeader($rs, $cols);
    }

    public static function adminEntryListValue($rs, $cols)
    {
        $cols['Links'] = '<td class="nowrap">' . App::meta()->getMetaRecordset($rs->post_meta, 'relatedEntries')->count() . '</td>';
    }

    public static function adminPostListValue($rs, $cols)
    {
        self::adminEntryListValue($rs, $cols);
    }
}
