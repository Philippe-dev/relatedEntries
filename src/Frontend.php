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

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\L10n;

class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('publicEntryBeforeContent', [self::class,  'publicEntryBeforeContent']);
        App::behavior()->addBehavior('publicEntryAfterContent', [self::class,  'publicEntryAfterContent']);
        App::behavior()->addBehavior('publicHeadContent', [self::class,  'publicHeadContent']);
        App::behavior()->addBehavior('initWidgets', [Widgets::class, 'initWidgets']);

        L10n::set(dirname(__FILE__) . '/locales/' . App::lang()->getLang() . '/main');

        return true;
    }

    public static function publicHeadContent()
    {
        // Settings

        if (!My::settings()->relatedEntries_enabled) {
            return;
        }

        echo My::cssLoad('style.css');
    }

    public static function publicEntryBeforeContent()
    {
        if (My::settings()->relatedEntries_enabled && My::settings()->relatedEntries_beforePost) {
            return FrontendTemplates::htmlBlock();
        }
    }

    public static function publicEntryAfterContent()
    {
        if (My::settings()->relatedEntries_enabled && My::settings()->relatedEntries_afterPost) {
            return FrontendTemplates::htmlBlock();
        }
    }
}
