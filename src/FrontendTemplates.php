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
use Dotclear\Database\MetaRecord;
use Dotclear\Plugin\widgets\WidgetsElement;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\listImages\FrontendHelper;

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
        if ($widget->isOffline()) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        if (!App::url()->isType('post')) {
            return '';
        }

        $meta = App::meta();

        $post_meta = App::frontend()->context()->posts instanceof MetaRecord ? App::frontend()->context()->posts->strField('post_meta') : '';
        $meta_rs   = $meta->getMetaStr($post_meta, 'relatedEntries');

        if ($meta_rs !== '') {
            //related posts
            App::blog()->withoutPassword(false);
            $params['post_id']    = $meta->splitMetaValues($meta_rs);
            $params['no_content'] = false;
            $params['post_type']  = ['post'];
            $rs                   = App::blog()->getPosts($params);

            $ret = $widget->getTitle();
            if (!(bool) $widget->get('images') || !App::plugins()->moduleExists('listImages')) {
                $ret .= '<ul>';
                while ($rs->fetch()) {
                    $ret .= '<li><a href="' . $rs->getURL() . '" title="' . Html::escapeHTML($rs->strField('post_title')) . '">' . $rs->strField('post_title') . '</a></li>';
                }

                $ret .= '</ul>';
            } else {
                // Récupération des options d'affichage des images
                $size     = is_string($size = $widget->get('size')) ? $size : '';
                $html_tag = is_string($html_tag = $widget->get('html_tag')) ? $html_tag : '';
                $link     = 'entry';
                $exif     = 0;
                $legend   = is_string($legend = $widget->get('legend')) ? $legend : '';
                $bubble   = 'entry';
                $from     = is_string($from = $widget->get('from')) ? $from : '';
                $start    = is_numeric($start = $widget->get('start')) ? abs((int) $start) : 0;
                $length   = is_numeric($length = $widget->get('length')) ? abs((int) $length) : 0;
                $class    = $widget->getClass();
                $alt      = is_string($alt = $widget->get('alt')) ? $alt : '';
                $img_dim  = 'none';
                $def_size = 'o';

                // Début d'affichage

                $ret .= '<' . ($html_tag === 'li' ? 'ul' : 'div') . ' class="relatedEntries-wrapper">';

                // Appel de la fonction de traitement pour chacun des billets
                while ($rs->fetch()) {
                    $ret .= FrontendHelper::EntryImages($size, $html_tag, $link, $exif, $legend, $bubble, $from, $start, $length, $class, $alt, $img_dim, $def_size, $rs);
                }

                // Fin d'affichage
                $ret .= '</' . ($html_tag === 'li' ? 'ul' : 'div') . '>' . "\n";
            }

            return $widget->renderDiv($widget->getContentOnly(), 'relatedEntries ' . $widget->getClass(), '', $ret);
        }

        return '';
    }

    /**
     * Public HTML rendering helper
     */
    public static function htmlBlock(): string
    {
        $posts = App::frontend()->context()->posts;
        if (!$posts instanceof MetaRecord) {
            return '';
        }

        $meta    = App::meta();
        $meta_rs = $meta->getMetaStr($posts->strField('post_meta'), 'relatedEntries');

        if ($posts->strField('post_type') === 'post' && $meta_rs !== '') {
            //related entries
            App::blog()->withoutPassword(false);

            $params = [
                'post_id'    => $meta->splitMetaValues($meta_rs),
                'no_content' => false,
                'post_type'  => ['post'],
            ];

            $rs = App::blog()->getPosts($params);

            if (App::plugins()->moduleExists('listImages') && My::settings()->getBool('images', false)) {
                //images display options
                $images_options = My::settings()->getStr('images_options', false);

                /**
                 * @var array{
                 *          size?: string,
                 *          html_tag?: string,
                 *          link?: string,
                 *          exif?: int,
                 *          legend?: string,
                 *          bubble?: string,
                 *          from?: string,
                 *          start?: int,
                 *          length?: int,
                 *          class?: string,
                 *          alt?: string,
                 *          img_dim?: string
                 *      } $img_options
                 */
                $img_options = unserialize($images_options);

                $size     = $img_options['size']     ?? 't';
                $html_tag = $img_options['html_tag'] ?? 'div';
                $link     = $img_options['link']     ?? 'entry';
                $exif     = $img_options['exif']     ?? 0;
                $legend   = $img_options['legend']   ?? 'none';
                $bubble   = 'entry';
                $from     = $img_options['from']   ?? 'full';
                $start    = $img_options['start']  ?? 1;
                $length   = $img_options['length'] ?? 1;
                $class    = $img_options['class']  ?? '';
                $alt      = $img_options['alt']    ?? 'inherit';
                $img_dim  = 'none';
                $def_size = 'o';

                $ret = My::settings()->getStr('title', false) !== '' ? '<h3>' . My::settings()->getStr('title', false) . '</h3>' : '';
                $ret .= '<' . ($html_tag === 'li' ? 'ul' : 'div') . ' class="relatedEntries">';

                //listImages plugin comes here
                while ($rs->fetch()) {
                    // Lien vers le billet d'origine
                    $url        = $rs->getURL();
                    $href       = $url;
                    $href_title = Html::escapeHTML($rs->strField('post_title'));

                    $ret .= '<a class="link_' . $link . '" href="' . $href . '" title="' . $href_title . '">';

                    $ret .= FrontendHelper::EntryImages($size, $html_tag, $link, $exif, $legend, $bubble, $from, $start, $length, $class, $alt, $img_dim, $def_size, $rs);
                }

                $ret .= '</' . ($html_tag === 'li' ? 'ul' : 'div') . '>' . "\n";

                echo $ret;
            } elseif (!App::plugins()->moduleExists('listImages') || !My::settings()->getBool('images', false)) {
                $ret = My::settings()->getStr('title', false) !== '' ? '<h3>' . My::settings()->getStr('title', false) . '</h3>' : '';
                $ret .= '<ul class="relatedEntries">';

                while ($rs->fetch()) {
                    $ret .= '<li><a href="' . $rs->getURL() . '" title="' . Html::escapeHTML($rs->strField('post_title')) . '">' . $rs->strField('post_title') . '</a></li>';
                }

                $ret .= '</ul>';

                echo $ret;
            }
        }

        return '';
    }
}
