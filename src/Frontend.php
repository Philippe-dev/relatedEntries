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
use dcNsProcess;
use html;
use l10n;
use tplEntryImages;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior('publicEntryBeforeContent', [self::class,  'publicEntryBeforeContent']);
        dcCore::app()->addBehavior('publicEntryAfterContent', [self::class,  'publicEntryAfterContent']);
        dcCore::app()->addBehavior('publicHeadContent', [self::class,  'publicHeadContent']);
        dcCore::app()->addBehavior('initWidgets', [Widgets::class, 'initWidgets']);

        l10n::set(dirname(__FILE__) . '/locales/' . dcCore::app()->lang . '/main');

        return true;
    }

    public static function publicHeadContent()
    {
        // Settings

        $s = dcCore::app()->blog->settings->relatedEntries;

        if (!$s->relatedEntries_enabled) {
            return;
        }

        $url = dcCore::app()->blog->getQmarkURL() . 'pf=relatedEntries';

        echo
        '<link rel="stylesheet" type="text/css" href="' . $url . '/css/style.css" />' . "\n";
    }

    public static function publicEntryBeforeContent()
    {
        // Settings

        $s = dcCore::app()->blog->settings->relatedEntries;

        if (!$s->relatedEntries_enabled) {
            return;
        }

        if (!$s->relatedEntries_beforePost) {
            return;
        }

        if (dcCore::app()->ctx->posts->post_type == 'post' && dcCore::app()->ctx->posts->post_meta != '') {
            //related entries
            $meta = dcCore::app()->meta;

            $r_ids                = $meta->getMetaStr(dcCore::app()->ctx->posts->post_meta, 'relatedEntries');
            $params['post_id']    = $meta->splitMetaValues($r_ids);
            $params['no_content'] = false;
            $params['post_type']  = ['post'];
            $rs                   = dcCore::app()->blog->getPosts($params);

            if (dcCore::app()->plugins->moduleExists('listImages') && $s->relatedEntries_images) {
                //images display options
                $img_options = unserialize($s->relatedEntries_images_options);

                $size     = $img_options['size'] ? $img_options['size'] : 't';
                $html_tag = $img_options['html_tag'] ? $img_options['html_tag'] : 'div';
                $link     = $img_options['link'] ? $img_options['link'] : 'entry';
                $exif     = $img_options['exif'] ? $img_options['exif'] : 0;
                $legend   = $img_options['legend'] ? $img_options['legend'] : 'none';
                $bubble   = $img_options['bubble'] ? $img_options['bubble'] : 'image';
                $from     = $img_options['from'] ? $img_options['from'] : 'full';
                $start    = $img_options['start'] ? $img_options['start'] : 1;
                $length   = $img_options['length'] ? $img_options['length'] : 1;
                $class    = $img_options['class'] ? $img_options['class'] : '';
                $alt      = $img_options['alt'] ? $img_options['alt'] : 'inherit';
                $img_dim  = $img_options['img_dim'] ? $img_options['img_dim'] : 0;
                $def_size = 'o';
                $ret      = $s->relatedEntries_title != '' ? '<h3>' . $s->relatedEntries_title . '</h3>' : '';
                $ret .= '<' . ($html_tag == 'li' ? 'ul' : 'div') . ' class="relatedEntries">';

                //listImages plugin comes here
                while ($rs->fetch()) {
                    $ret .= tplEntryImages::EntryImagesHelper($size, $html_tag, $link, $exif, $legend, $bubble, $from, $start, $length, $class, $alt, $img_dim, $def_size, $rs);
                }

                $ret .= '</' . ($html_tag == 'li' ? 'ul' : 'div') . '>' . "\n";

                echo $ret;
            } elseif (!dcCore::app()->plugins->moduleExists('listImages') || !$s->relatedEntries_images) {
                $ret = $s->relatedEntries_title != '' ? '<h3>' . $s->relatedEntries_title . '</h3>' : '';
                $ret .= '<ul class="relatedEntries">';

                while ($rs->fetch()) {
                    $ret .= '<li><a href="' . $rs->getURL() . '" title="' . html::escapeHTML($rs->post_title) . '">' . $rs->post_title . '</a></li>';
                }
                $ret .= '</ul>';

                echo $ret;
            }
        }
    }

    public static function publicEntryAfterContent()
    {
        // Settings

        $s = dcCore::app()->blog->settings->relatedEntries;

        if (!$s->relatedEntries_enabled) {
            return;
        }

        if (!$s->relatedEntries_afterPost) {
            return;
        }

        if (dcCore::app()->ctx->posts->post_type == 'post' && dcCore::app()->ctx->posts->post_meta != '') {
            //related entries
            $meta = dcCore::app()->meta;

            $r_ids                = $meta->getMetaStr(dcCore::app()->ctx->posts->post_meta, 'relatedEntries');
            $params['post_id']    = $meta->splitMetaValues($r_ids);
            $params['no_content'] = false;
            $params['post_type']  = ['post'];
            $rs                   = dcCore::app()->blog->getPosts($params);

            if (dcCore::app()->plugins->moduleExists('listImages') && $s->relatedEntries_images) {
                //images display options
                $img_options = unserialize($s->relatedEntries_images_options);

                $size     = $img_options['size'] ? $img_options['size'] : 't';
                $html_tag = $img_options['html_tag'] ? $img_options['html_tag'] : 'div';
                $link     = $img_options['link'] ? $img_options['link'] : 'entry';
                $exif     = $img_options['exif'] ? $img_options['exif'] : 0;
                $legend   = $img_options['legend'] ? $img_options['legend'] : 'none';
                $bubble   = $img_options['bubble'] ? $img_options['bubble'] : 'image';
                $from     = $img_options['from'] ? $img_options['from'] : 'full';
                $start    = $img_options['start'] ? $img_options['start'] : 1;
                $length   = $img_options['length'] ? $img_options['length'] : 1;
                $class    = $img_options['class'] ? $img_options['class'] : '';
                $alt      = $img_options['alt'] ? $img_options['alt'] : 'inherit';
                $img_dim  = $img_options['img_dim'] ? $img_options['img_dim'] : 0;
                $def_size = 'o';
                $ret      = $s->relatedEntries_title != '' ? '<h3>' . $s->relatedEntries_title . '</h3>' : '';
                $ret .= '<' . ($html_tag == 'li' ? 'ul' : 'div') . ' class="relatedEntries">';

                //listImages plugin comes here
                while ($rs->fetch()) {
                    $ret .= tplEntryImages::EntryImagesHelper($size, $html_tag, $link, $exif, $legend, $bubble, $from, $start, $length, $class, $alt, $img_dim, $def_size, $rs);
                }

                $ret .= '</' . ($html_tag == 'li' ? 'ul' : 'div') . '>' . "\n";

                echo $ret;
            } elseif (!dcCore::app()->plugins->moduleExists('listImages') || !$s->relatedEntries_images) {
                $ret = $s->relatedEntries_title != '' ? '<h3>' . $s->relatedEntries_title . '</h3>' : '';
                $ret .= '<ul class="relatedEntries">';

                while ($rs->fetch()) {
                    $ret .= '<li><a href="' . $rs->getURL() . '" title="' . html::escapeHTML($rs->post_title) . '">' . $rs->post_title . '</a></li>';
                }
                $ret .= '</ul>';

                echo $ret;
            }
        }
    }
}
