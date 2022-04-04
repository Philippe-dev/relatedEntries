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
    'Related entries',							// Name
    'Add links to other related posts',			// Description
    'Philippe aka amalgame',					// Author
    '2.6.4',                   					// Version
    [
        'requires' => [['core', '2.16']],   	// Dependencies
        'permissions' => 'usage,contentadmin', 	// Permissions
        'type' => 'plugin',             	    // Type
        'priority' => 3000                 	    // Priority

    ]
);
