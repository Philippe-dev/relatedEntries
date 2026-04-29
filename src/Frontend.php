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

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

class Frontend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('publicEntryBeforeContent', self::publicEntryBeforeContent(...));
        App::behavior()->addBehavior('publicEntryAfterContent', self::publicEntryAfterContent(...));
        App::behavior()->addBehavior('publicHeadContent', self::publicHeadContent(...));
        App::behavior()->addBehavior('initWidgets', Widgets::initWidgets(...));

        return true;
    }

    public static function publicHeadContent()
    {
        // Settings

        if (!My::settings()->enabled) {
            return;
        }

        echo My::cssLoad('style.css');
    }

    public static function publicEntryBeforeContent()
    {
        if (My::settings()->enabled && My::settings()->beforePost) {
            return FrontendTemplates::htmlBlock();
        }
    }

    public static function publicEntryAfterContent()
    {
        if (My::settings()->enabled && My::settings()->afterPost) {
            return FrontendTemplates::htmlBlock();
        }
    }
}
