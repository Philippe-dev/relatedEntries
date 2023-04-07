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

class FrontendTemplate
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
            $rs                   = dcCore::app()->blog->getPosts($params);
            $ret                  = ($widget->title ? $widget->renderTitle(html::escapeHTML($widget->title)) : '');

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
}
