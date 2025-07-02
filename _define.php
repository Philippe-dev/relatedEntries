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
$this->registerModule(
    'Related entries',
    'Add links to other related posts',
    'Philippe aka amalgame',
    '5.2',
    [
        'date'        => '2025-07-02T00:00:13+0100',
        'requires'    => [['core', '2.34']],
        'permissions' => 'My',
        'priority'    => 3000,
        'type'        => 'plugin',
        'support'     => 'https://github.com/Philippe-dev/relatedEntries',
    ]
);
