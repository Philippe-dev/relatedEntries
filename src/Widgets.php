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
use Dotclear\Plugin\widgets\WidgetsStack;

class Widgets
{
    /**
     * Initializes the pages widget.
     *
     * @param      WidgetsStack  $widgets  The widgets
     */
    public static function initWidgets(WidgetsStack $widgets): void
    {
        $widget = $widgets->create(
            'relatedEntriesWidget',
            __('Related posts'),
            FrontendTemplates::relatedEntriesWidget(...),
            null,
            __('Related entries to current post')
        );

        // Widget title
        $widget->addTitle(__('Related posts'));
        // Only if listImages plugin

        if (App::plugins()->moduleExists('listImages')) {
            $widget->setting('images', __('Extract images from related posts'), 0, 'check');

            $widget->setting(
                'from',
                __('Images origin:'),
                1,
                'combo',
                [__('full post') => 'full', __('post excerpt') => 'excerpt', __('post content') => 'content']
            );

            $widget->setting(
                'size',
                __('Image size'),
                1,
                'combo',
                [__('square') => 'sq', __('thumbnail') => 't',  __('small') => 's', __('medium') => 'm', __('original') => 'o']
            );

            $widget->setting(
                'alt',
                __('Images alt attribute:'),
                'inherit',
                'combo',
                [__('image title') => 'inherit', __('no alt') => 'none']
            );

            $widget->setting('start', __('First image to extract:'), '1');

            $widget->setting('length', __('Number of images to extract:'), '1');

            $widget->setting(
                'legend',
                __('Legend:'),
                1,
                'combo',
                [__('no legend') => 'none', __('image title') => 'image', __('entry title') => 'entry']
            );

            $widget->setting(
                'html_tag',
                __('HTML tag around image:'),
                'div',
                'combo',
                ['list' => 'li', 'div' => 'div', __('no tag') => 'none']
            );

            $widget->setting('class', __('CSS class on images:'), '', 'text');
        }

        $widget->addContentOnly();
        $widget->addClass();
        $widget->addOffline();
    }
}
