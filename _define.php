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
    '6.2',
    [
        'date'        => '2026-06-20T00:00:16+0100',
        'requires'    => [['core', '2.38']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/Philippe-dev/relatedEntries',
    ]
);
