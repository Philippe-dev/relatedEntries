<?php
/**
 * @brief Related Entries, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Philippe aka amalgame
 *
 * @copyright Philippe Hénaff philippe@dissitou.org
 * @copyright GPL-2.0 [https://www.gnu.org/licenses/gpl-2.0.html]
 */

if (!defined('DC_RC_PATH')) {
    return;
}

$core->addBehavior('initWidgets', array('relatedEntriesWidgetBehaviors','initWidgets'));

l10n::set(dirname(__FILE__).'/locales/'.$_lang.'/main');

class relatedEntriesWidgetBehaviors
{
    public static function initWidgets($w)
    {
        global $core;
        
        $s =& $core->blog->settings->relatedEntries;
        
        if (!$s->relatedEntries_enabled) {
            return;
        }
            
        $w->create(
            'relatedEntriesWidget',
            __('Related posts'),
            array('relatedEntriesWidget','Widget'),
            null,
            __('Related entries to current post')
        );

        // Widget title
        $w->relatedEntriesWidget->setting('title', __('Title:'), __('Related posts'));

        // Only if listImages plugin
        
        if ($core->plugins->moduleExists('listImages')) {
            $w->relatedEntriesWidget->setting('relatedEntries_images', __('Extract images from related posts'), 0, 'check');
            
            $w->relatedEntriesWidget->setting(
                'from',
                __('Images origin:'),
                1,
                'combo',

                array(__('full post') => 'full', __('post excerpt') => 'excerpt', __('post content') => 'content')
            );
            
            $w->relatedEntriesWidget->setting(
                'size',
                __('Image size'),
                1,
                'combo',

                array(__('square') => 'sq',__('thumbnail') => 't',  __('small') => 's', __('medium') => 'm', __('original') => 'o')
            );
            
            $w->relatedEntriesWidget->setting('img_dim', __('Include images dimensions'), 0, 'check');
                
            $w->relatedEntriesWidget->setting(
                'alt',
                __('Images alt attribute:'),
                'inherit',
                'combo',

                array(__('image title') => 'inherit', __('no alt') => 'none')
            );
                
            $w->relatedEntriesWidget->setting('start', __('First image to extract:'), '1');

            $w->relatedEntriesWidget->setting('length', __('Number of images to extract:'), '1');
            
            $w->relatedEntriesWidget->setting(
                'legend',
                __('Legend:'),
                1,
                'combo',

                array(__('no legend') => 'none', __('image title') => 'image', __('entry title') => 'entry')
            );
            
            $w->relatedEntriesWidget->setting(
                'html_tag',
                __('HTML tag around image:'),
                'div',
                'combo',

                array('list' => 'li', 'div' => 'div', __('no tag') => 'none')
            );
            
            $w->relatedEntriesWidget->setting('class', __('CSS class on images:'), '', 'text');

            $w->relatedEntriesWidget->setting(
                'link',
                __('Links destination:'),
                'entry',
                'combo',

                array(__('original images') => 'image', __('related posts') => 'entry', __('no link') => 'none')
            );

            $w->relatedEntriesWidget->setting(
                'bubble',
                __('Bubble:'),
                1,
                'combo',

                array(__('no bubble') => 'none', __('image title') => 'image', __('entry title') => 'entry')
            );
        }
    }
}
