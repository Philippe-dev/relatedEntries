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

if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'Related entries',
        'Add links to other related posts',
        'Philippe aka amalgame and contributors',
        '7.0',
        [
            'date'        => '2026-11-26T00:00:16+0100',
            'requires'    => [['core', '2.40']],
            'permissions' => 'My',
            'type'        => 'plugin',
            'support'     => 'https://github.com/Philippe-dev/relatedEntries',
        ]
    );
}
