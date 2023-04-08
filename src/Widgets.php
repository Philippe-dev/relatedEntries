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

use dcCore;
use Dotclear\Plugin\widgets\Widgets as dcWidgets;
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
        
        $widgets->create(
            'relatedEntriesWidget',
            __('Related posts'),
            [FrontendTemplates::class, 'relatedEntriesWidget'],
            null,
            __('Related entries to current post')
        );

        // Widget title
        $widgets->relatedEntriesWidget->addTitle(__('Related posts'));
        // Only if listImages plugin

        if (dcCore::app()->plugins->moduleExists('listImages')) {
            $widgets->relatedEntriesWidget->setting('relatedEntries_images', __('Extract images from related posts'), 0, 'check');

            $widgets->relatedEntriesWidget->setting(
                'from',
                __('Images origin:'),
                1,
                'combo',
                [__('full post') => 'full', __('post excerpt') => 'excerpt', __('post content') => 'content']
            );

            $widgets->relatedEntriesWidget->setting(
                'size',
                __('Image size'),
                1,
                'combo',
                [__('square') => 'sq', __('thumbnail') => 't',  __('small') => 's', __('medium') => 'm', __('original') => 'o']
            );

            $widgets->relatedEntriesWidget->setting('img_dim', __('Include images dimensions'), 0, 'check');

            $widgets->relatedEntriesWidget->setting(
                'alt',
                __('Images alt attribute:'),
                'inherit',
                'combo',
                [__('image title') => 'inherit', __('no alt') => 'none']
            );

            $widgets->relatedEntriesWidget->setting('start', __('First image to extract:'), '1');

            $widgets->relatedEntriesWidget->setting('length', __('Number of images to extract:'), '1');

            $widgets->relatedEntriesWidget->setting(
                'legend',
                __('Legend:'),
                1,
                'combo',
                [__('no legend') => 'none', __('image title') => 'image', __('entry title') => 'entry']
            );

            $widgets->relatedEntriesWidget->setting(
                'html_tag',
                __('HTML tag around image:'),
                'div',
                'combo',
                ['list' => 'li', 'div' => 'div', __('no tag') => 'none']
            );

            $widgets->relatedEntriesWidget->setting('class', __('CSS class on images:'), '', 'text');

            $widgets->relatedEntriesWidget->setting(
                'link',
                __('Links destination:'),
                'entry',
                'combo',
                [__('original images') => 'image', __('related posts') => 'entry', __('no link') => 'none']
            );

            $widgets->relatedEntriesWidget->setting(
                'bubble',
                __('Bubble:'),
                1,
                'combo',
                [__('no bubble') => 'none', __('image title') => 'image', __('entry title') => 'entry']
            );
        }

        $widgets->relatedEntriesWidget->addContentOnly();
        $widgets->relatedEntriesWidget->addClass();
        $widgets->relatedEntriesWidget->addOffline();
    }

}
