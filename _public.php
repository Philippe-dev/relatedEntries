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

if (!defined('DC_RC_PATH')) {
    return;
}

require dirname(__FILE__) . '/_widget.php';

dcCore::app()->addBehavior('publicEntryBeforeContent', ['relatedEntriesPublic', 'publicEntryBeforeContent']);
dcCore::app()->addBehavior('publicEntryAfterContent', ['relatedEntriesPublic', 'publicEntryAfterContent']);
dcCore::app()->addBehavior('publicHeadContent', ['relatedEntriesPublic', 'publicHeadContent']);

l10n::set(dirname(__FILE__) . '/locales/' . $_lang . '/main');

class relatedEntriesWidget
{
    public static function Widget($w)
    {

        $s = dcCore::app()->blog->settings->relatedEntries;

        if (!$s->relatedEntries_enabled) {
            return;
        }

        if (dcCore::app()->url->type != 'post') {
            return;
        }

        $id = dcCore::app()->ctx->posts->post_id;

        //current post
        $params['post_id'] = $id;
        $params['no_content'] = true;
        $params['post_type'] = ['post'];

        $rs = dcCore::app()->blog->getPosts($params);

        $meta = dcCore::app()->meta;
        $meta_rs = $meta->getMetaStr($rs->post_meta, 'relatedEntries');
        if ($meta_rs != '') {
            //related posts
            $params['post_id'] = $meta->splitMetaValues($meta_rs);
            $params['no_content'] = false;
            $params['post_type'] = ['post'];
            $rs = dcCore::app()->blog->getPosts($params);
            $ret = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '');

            if (!$w->relatedEntries_images || !dcCore::app()->plugins->moduleExists('listImages')) {
                $ret .= '<ul>';
                while ($rs->fetch()) {
                    $ret .= '<li><a href="' . $rs->getURL() . '" title="' . html::escapeHTML($rs->post_title) . '">' . $rs->post_title . '</a></li>';
                }
                $ret .= '</ul>';
            } else {
                // Récupération des options d'affichage des images
                $size = $w->size;
                $html_tag = $w->html_tag;
                $link = $w->link;
                $exif = 0;
                $legend = $w->legend;
                $bubble = $w->bubble;
                $from = $w->from;
                $start = abs((integer) $w->start);
                $length = abs((integer) $w->length);
                $class = $w->class;
                $alt = $w->alt;
                $img_dim = abs((integer) $w->img_dim);
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

            return $w->renderDiv($w->content_only, 'relatedEntries ' . $w->class, '', $ret);
        }
    }
}

class relatedEntriesPublic
{
    public static function publicHeadContent()
    {
        // Settings

        $s = dcCore::app()->blog->settings->relatedEntries;

        if (!$s->relatedEntries_enabled) {
            return;
        }

        $url = dcCore::app()->blog->getQmarkURL() . 'pf=' . basename(dirname(__FILE__));

        echo
        '<link rel="stylesheet" type="text/css" href="' . $url . '/css/style.css" />' . "\n";
    }

    public static function thisPostrelatedEntries($id)
    {
        $meta = dcCore::app()->meta;
        $params['post_id'] = $id;
        $params['no_content'] = false;
        $params['post_type'] = ['post'];

        $rs = dcCore::app()->blog->getPosts($params);
        return $meta->getMetaStr($rs->post_meta, 'relatedEntries');
    }

    public static function publicEntryBeforeContent($core, $_ctx)
    {
        // Settings

        $s = dcCore::app()->blog->settings->relatedEntries;

        if (!$s->relatedEntries_enabled) {
            return;
        }
        if (!$s->relatedEntries_beforePost) {
            return;
        }
        if (dcCore::app()->ctx->posts->post_type == 'post' && self::thisPostrelatedEntries(dcCore::app()->ctx->posts->post_id) != '') {
            //related entries
            $meta = dcCore::app()->meta;

            $r_ids = self::thisPostrelatedEntries(dcCore::app()->ctx->posts->post_id);
            $params['post_id'] = $meta->splitMetaValues($r_ids);
            $rs = dcCore::app()->blog->getPosts($params);

            if (dcCore::app()->plugins->moduleExists('listImages') && $s->relatedEntries_images) {
                //images display options
                $img_options = unserialize($s->relatedEntries_images_options);

                $size = $img_options['size'] ? $img_options['size'] : 't';
                $html_tag = $img_options['html_tag'] ? $img_options['html_tag'] : 'div';
                $link = $img_options['link'] ? $img_options['link'] : 'entry';
                $exif = $img_options['exif'] ? $img_options['exif'] : 0;
                $legend = $img_options['legend'] ? $img_options['legend'] : 'none';
                $bubble = $img_options['bubble'] ? $img_options['bubble'] : 'image';
                $from = $img_options['from'] ? $img_options['from'] : 'full';
                $start = $img_options['start'] ? $img_options['start'] : 1;
                $length = $img_options['length'] ? $img_options['length'] : 1;
                $class = $img_options['class'] ? $img_options['class'] : '';
                $alt = $img_options['alt'] ? $img_options['alt'] : 'inherit';
                $img_dim = $img_options['img_dim'] ? $img_options['img_dim'] : 0;
                $def_size = 'o';
                $ret = $s->relatedEntries_title != '' ? '<h3>' . $s->relatedEntries_title . '</h3>' : '';
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

    public static function publicEntryAfterContent($core, $_ctx)
    {
        // Settings

        $s = dcCore::app()->blog->settings->relatedEntries;

        if (!$s->relatedEntries_enabled) {
            return;
        }
        if (!$s->relatedEntries_afterPost) {
            return;
        }
        if (dcCore::app()->ctx->posts->post_type == 'post' && self::thisPostrelatedEntries(dcCore::app()->ctx->posts->post_id) != '') {
            //related entries
            $meta = dcCore::app()->meta;

            $r_ids = self::thisPostrelatedEntries(dcCore::app()->ctx->posts->post_id);
            $params['post_id'] = $meta->splitMetaValues($r_ids);
            $rs = dcCore::app()->blog->getPosts($params);

            if (dcCore::app()->plugins->moduleExists('listImages') && $s->relatedEntries_images) {
                //images display options
                $img_options = unserialize($s->relatedEntries_images_options);

                $size = $img_options['size'] ? $img_options['size'] : 't';
                $html_tag = $img_options['html_tag'] ? $img_options['html_tag'] : 'div';
                $link = $img_options['link'] ? $img_options['link'] : 'entry';
                $exif = $img_options['exif'] ? $img_options['exif'] : 0;
                $legend = $img_options['legend'] ? $img_options['legend'] : 'none';
                $bubble = $img_options['bubble'] ? $img_options['bubble'] : 'image';
                $from = $img_options['from'] ? $img_options['from'] : 'full';
                $start = $img_options['start'] ? $img_options['start'] : 1;
                $length = $img_options['length'] ? $img_options['length'] : 1;
                $class = $img_options['class'] ? $img_options['class'] : '';
                $alt = $img_options['alt'] ? $img_options['alt'] : 'inherit';
                $img_dim = $img_options['img_dim'] ? $img_options['img_dim'] : 0;
                $def_size = 'o';
                $ret = $s->relatedEntries_title != '' ? '<h3>' . $s->relatedEntries_title . '</h3>' : '';
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
