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
use dcNsProcess;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        $module = basename(dirname(__DIR__));
        $check  = dcCore::app()->newVersion($module, dcCore::app()->plugins->moduleInfo($module, 'version'));

        self::$init = defined('DC_CONTEXT_ADMIN') && $check;

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        $settings = dcCore::app()->blog->settings->relatedEntries;

        $settings->put('relatedEntries_enabled', false, 'boolean', 'Enable related entries', false, true);
        $settings->put('relatedEntries_images', false, 'boolean', 'Display related entries links as images', false, true);
        $settings->put('relatedEntries_beforePost', false, 'boolean', 'Display related entries before post content', false, true);
        $settings->put('relatedEntries_afterPost', true, 'boolean', 'Display related entries after post content', false, true);
        $settings->put('relatedEntries_title', __('Related posts'), 'string', 'Related entries block title', false, true);

        $opts = [
            'size'     => 't',
            'html_tag' => 'div',
            'link'     => 'entry',
            'exif'     => 0,
            'legend'   => 'none',
            'bubble'   => 'image',
            'from'     => 'full',
            'start'    => 1,
            'length'   => 1,
            'class'    => '',
            'alt'      => 'inherit',
            'img_dim'  => 0,
        ];

        $settings->put('relatedEntries_images_options', serialize($opts), 'string', 'Related entries images options', false, true);

        return true;
    }
}
