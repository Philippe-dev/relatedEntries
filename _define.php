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
    '6.1',
    [
        'date'        => '2026-04-29T00:00:16+0100',
        'requires'    => [['core', '2.36']],
        'permissions' => 'My',
        'priority'    => 3000,
        'type'        => 'plugin',
        'support'     => 'https://github.com/Philippe-dev/relatedEntries',
    ]
);
