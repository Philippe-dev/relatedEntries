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
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Exception;

class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            $settings = My::settings();

            // Update
            $old_version = App::version()->getVersion(My::id());

            if (version_compare((string) $old_version, '6.1', '<')) {
                // Change settings names (remove relatedEntries_ prefix in them)
                $rename = static function (string $name, BlogWorkspaceInterface $settings): void {
                    if ($settings->settingExists('relatedEntries_' . $name, true)) {
                        $settings->rename('relatedEntries_' . $name, $name);
                    }
                };

                foreach ([
                    'relatedEntries_enabled',
                    'relatedEntries_title',
                    'relatedEntries_images',
                    'relatedEntries_beforePost',
                    'relatedEntries_afterPost',
                    'relatedEntries_images_options',
                ] as $value) {
                    $rename($value, $settings);
                }
            }

            $settings->put('enabled', true, App::blogWorkspace()::NS_BOOL, 'Enable related entries', false, true);
            $settings->put('title', __('Related posts'), App::blogWorkspace()::NS_STRING, 'Title for related entries', false, true);
            $settings->put('beforePost', false, App::blogWorkspace()::NS_BOOL, 'Display related entries before post content', false, true);
            $settings->put('afterPost', true, App::blogWorkspace()::NS_BOOL, 'Display related entries after post content', false, true);
            $settings->put('images', false, App::blogWorkspace()::NS_BOOL, 'Display images for related entries', false, true);

            $opts = [
                'size'     => 't',
                'html_tag' => 'div',
                'link'     => 'entry',
                'exif'     => 0,
                'legend'   => 'none',
                'bubble'   => 'image',
                'from'     => 'full',
                'start'    => 1,
                'length'   => 1,
                'class'    => '',
                'alt'      => 'inherit',
                'img_dim'  => 0,
            ];

            $settings->put('images_options', serialize($opts), App::blogWorkspace()::NS_STRING, 'Related entries images options', false, true);
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }
}
