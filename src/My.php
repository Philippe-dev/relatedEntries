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
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    /**
     * Current admin page url
     */
    public static function url(): string
    {
        return dcCore::app()->admin->getPageURL();
    }
}
