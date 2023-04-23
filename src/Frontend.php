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
use Dotclear\Helper\L10n;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior('publicEntryBeforeContent', [self::class,  'publicEntryBeforeContent']);
        dcCore::app()->addBehavior('publicEntryAfterContent', [self::class,  'publicEntryAfterContent']);
        dcCore::app()->addBehavior('publicHeadContent', [self::class,  'publicHeadContent']);
        dcCore::app()->addBehavior('initWidgets', [Widgets::class, 'initWidgets']);

        L10n::set(dirname(__FILE__) . '/locales/' . dcCore::app()->lang . '/main');

        return true;
    }

    public static function publicHeadContent()
    {
        // Settings

        $settings = dcCore::app()->blog->settings->relatedEntries;

        if (!$settings->relatedEntries_enabled) {
            return;
        }

        $url = dcCore::app()->blog->getQmarkURL() . 'pf=relatedEntries';

        echo
        '<link rel="stylesheet" type="text/css" href="' . $url . '/css/style.css" />' . "\n";
    }

    public static function publicEntryBeforeContent()
    {
        $settings = dcCore::app()->blog->settings->relatedEntries;

        if ($settings->relatedEntries_enabled && $settings->relatedEntries_beforePost) {
            return FrontendTemplates::htmlBlock();
        }
    }

    public static function publicEntryAfterContent()
    {
        $settings = dcCore::app()->blog->settings->relatedEntries;

        if ($settings->relatedEntries_enabled && $settings->relatedEntries_afterPost) {
            return FrontendTemplates::htmlBlock();
        }
    }
}
