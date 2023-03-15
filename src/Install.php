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

        $s = dcCore::app()->blog->settings->colorbox;

        $opts = [
            'transition'     => 'elastic',
            'speed'          => '350',
            'title'          => '',
            'width'          => '',
            'height'         => '',
            'innerWidth'     => '',
            'innerHeight'    => '',
            'initialWidth'   => '300',
            'initialHeight'  => '100',
            'maxWidth'       => '',
            'maxHeight'      => '',
            'scalePhotos'    => true,
            'scrolling'      => true,
            'iframe'         => false,
            'opacity'        => '0.85',
            'open'           => false,
            'preloading'     => true,
            'overlayClose'   => true,
            'loop'           => true,
            'slideshow'      => false,
            'slideshowSpeed' => '2500',
            'slideshowAuto'  => false,
            'slideshowStart' => __('Start slideshow'),
            'slideshowStop'  => __('Stop slideshow'),
            'current'        => __('{current} of {total}'),
            'previous'       => __('previous'),
            'next'           => __('next'),
            'close'          => __('close'),
            'onOpen'         => '',
            'onLoad'         => '',
            'onComplete'     => '',
            'onCleanup'      => '',
            'onClosed'       => '',
        ];

        $s->put('colorbox_enabled', false, 'boolean', 'Enable Colorbox plugin', false, true);
        $s->put('colorbox_theme', '3', 'integer', 'Colorbox theme', false, true);
        $s->put('colorbox_zoom_icon', false, 'boolean', 'Enable Colorbox zoom icon', false, true);
        $s->put('colorbox_zoom_icon_permanent', false, 'boolean', 'Enable permanent Colorbox zoom icon', false, true);
        $s->put('colorbox_position', false, 'boolean', 'Colorbox zoom icon position', false, true);
        $s->put('colorbox_user_files', 'public', 'boolean', 'Colorbox user files', false, true);
        $s->put('colorbox_selectors', '', 'string', 'Colorbox selectors', false, true);
        $s->put('colorbox_legend', 'alt', 'string', 'Colorbox legend', false, true);
        $s->put('colorbox_advanced', serialize($opts), 'string', 'Colorbox advanced options', false, true);

        return true;
    }
}
