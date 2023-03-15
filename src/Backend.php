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

use dcAdmin;
use dcAuth;
use dcCore;
use dcFavorites;
use dcPage;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
            __('Colorbox'),
            dcCore::app()->adminurl->get('admin.plugin.colorbox'),
            [dcPage::getPF('colorbox/icon.svg'), dcPage::getPF('colorbox/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.colorbox')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog->id)
        );

        /* Register favorite */
        dcCore::app()->addBehavior('adminDashboardFavoritesV2', function (dcFavorites $favs) {
            $favs->register('colorbox', [
                'title'       => __('Colorbox'),
                'url'         => dcCore::app()->adminurl->get('admin.plugin.colorbox'),
                'small-icon'  => [dcPage::getPF('colorbox/icon.svg'), dcPage::getPF('colorbox/icon-dark.svg')],
                'large-icon'  => [dcPage::getPF('colorbox/icon.svg'), dcPage::getPF('colorbox/icon-dark.svg')],
                'permissions' => dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CONTENT_ADMIN]),
            ]);
        });

        return true;
    }
}
