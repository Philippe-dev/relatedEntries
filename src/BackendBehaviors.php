<?php
/**
 * @brief Related Entries, a plugin for Dotclear 2
 *
 * @package    Dotclear
 * @subpackage Plugins
 *
 * @author Philippe aka amalgame
 *
 * @copyright AGPL-3.0
 */

declare(strict_types=1);

namespace Dotclear\Plugin\relatedEntries;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;

class BackendBehaviors
{
    /**
     * @param      ArrayObject<string, mixed>  $cols   The cols
     */
    public static function adminColumnsLists(ArrayObject $cols): string
    {
        $cols['posts'][1]['Links'] = [true, __('Links')];

        return '';
    }

    /**
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    private static function adminEntryListHeader(ArrayObject $cols): string
    {
        $cols['Links'] = (new Th())
            ->scope('col')
            ->text(__('Links'))
        ->render();

        return '';
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    public static function adminPostListHeader(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListHeader($cols);
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    private static function adminEntryListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        $cols['Links'] = (new Td())
            ->class(['nowrap','count'])
            ->text(App::meta()->getMetaRecordset($rs->post_meta, 'relatedEntries')->count())
            ->render();

        return '';
    }

    /**
     * @param      MetaRecord                     $rs     The recordset
     * @param      ArrayObject<string, string>    $cols   The cols
     */
    public static function adminPostListValue(MetaRecord $rs, ArrayObject $cols): string
    {
        return self::adminEntryListValue($rs, $cols);
    }
}
