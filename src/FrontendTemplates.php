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
use Dotclear\Plugin\widgets\WidgetsElement;
use html;
use tplEntryImages;

class FrontendTemplates
{
    /**
     * Widget public rendering helper
     *
     * @param      WidgetsElement  $widget  The widget
     *
     * @return     string
     */
    public static function relatedEntriesWidget(WidgetsElement $widget)
    {
        $params = [];
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(dcCore::app()->url->type)) {
            return '';
        }

        if (dcCore::app()->url->type != 'post') {
            return;
        }

        $meta = dcCore::app()->meta;

        $meta_rs = $meta->getMetaStr(dcCore::app()->ctx->posts->post_meta, 'relatedEntries');

        if ($meta_rs != '') {
            //related posts
            $params['post_id']    = $meta->splitMetaValues($meta_rs);
            $params['no_content'] = false;
            $params['post_type']  = ['post'];
            dcCore::app()->blog->withoutPassword(false);
            $rs  = dcCore::app()->blog->getPosts($params);
            $ret = ($widget->title ? $widget->renderTitle(html::escapeHTML($widget->title)) : '');

            if (!$widget->relatedEntries_images || !dcCore::app()->plugins->moduleExists('listImages')) {
                $ret .= '<ul>';
                while ($rs->fetch()) {
                    $ret .= '<li><a href="' . $rs->getURL() . '" title="' . html::escapeHTML($rs->post_title) . '">' . $rs->post_title . '</a></li>';
                }
                $ret .= '</ul>';
            } else {
                // Récupération des options d'affichage des images
                $size     = $widget->size;
                $html_tag = $widget->html_tag;
                $link     = $widget->link;
                $exif     = 0;
                $legend   = $widget->legend;
                $bubble   = $widget->bubble;
                $from     = $widget->from;
                $start    = abs((int) $widget->start);
                $length   = abs((int) $widget->length);
                $class    = $widget->class;
                $alt      = $widget->alt;
                $img_dim  = abs((int) $widget->img_dim);
                $def_size = 'o';

                // Début d'affichage

                $ret .= '<' . ($html_tag == 'li' ? 'ul' : 'div') . ' class="relatedEntries-wrapper">';

                // Appel de la fonction de traitement pour chacun des billets
                while ($rs->fetch()) {
                    $ret .= tplEntryImages::EntryImagesHelper($size, $html_tag, $link, $exif, $legend, $bubble, $from, $start, $length, $class, $alt, $img_dim, $def_size, $rs);
                }

                // Fin d'affichage
                $ret .= '</' . ($html_tag == 'li' ? 'ul' : 'div') . '>' . "\n";
            }

            return $widget->renderDiv((bool) $widget->content_only, 'relatedEntries ' . $widget->class, '', $ret);
        }
    }

    /**
     * Public HTML rendering helper
     *
     * @return     string
     */
    public static function relatedEntriesHtml()
    {
        $s = dcCore::app()->blog->settings->relatedEntries;

        if (dcCore::app()->ctx->posts->post_type == 'post' && self::thisPostrelatedEntries(dcCore::app()->ctx->posts->post_id) != '') {
            //related entries
            $meta = dcCore::app()->meta;

            $r_ids                = $meta->getMetaStr(dcCore::app()->ctx->posts->post_meta, 'relatedEntries');
            $params['post_id']    = $meta->splitMetaValues($r_ids);
            $params['no_content'] = false;
            $params['post_type']  = ['post'];
            dcCore::app()->blog->withoutPassword(false);
            $rs = dcCore::app()->blog->getPosts($params);

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

    public static function thisPostrelatedEntries($id)
    {
        $meta                 = dcCore::app()->meta;
        $params['post_id']    = $id;
        $params['no_content'] = false;
        $params['post_type']  = ['post'];
        dcCore::app()->blog->withoutPassword(false);

        $rs = dcCore::app()->blog->getPosts($params);

        return $meta->getMetaStr($rs->post_meta, 'relatedEntries');
    }
}
