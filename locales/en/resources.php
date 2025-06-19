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


App::backend()->resources()->set('help', 'config', __DIR__ . '/help/config_help.html');
App::backend()->resources()->set('help', 'manage', __DIR__ . '/help/manage_help.html');
App::backend()->resources()->set('help', 'posts', __DIR__ . '/help/posts_help.html');
App::backend()->resources()->set('help', 'post', __DIR__ . '/help/post_help.html');

