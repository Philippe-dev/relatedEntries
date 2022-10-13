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

if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    'Related entries',
    'Add links to other related posts',
    'Philippe aka amalgame',
    [
        'requires' => [['core', '2.23']],
        'permissions' => dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CONTENT_ADMIN]), 	// Permissions
        'type' => 'plugin',
        'priority' => 3000

    ]
);
