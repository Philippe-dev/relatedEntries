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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;

class Config
{
    use TraitProcess;
    
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        self::status(My::checkContext(My::CONFIG));

        return self::status();
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Image size combo
        $img_size_combo = [];
        $media          = App::media();

        $img_size_combo[__('square')]    = 'sq';
        $img_size_combo[__('thumbnail')] = 't';
        $img_size_combo[__('small')]     = 's';
        $img_size_combo[__('medium')]    = 'm';
        $img_size_combo[__('original')]  = 'o';
        foreach ($media->thumb_sizes as $code => $size) {
            $img_size_combo[__($size[2])] = $code;
        }

        // Html tag combo
        $html_tag_combo = [
            __('div')    => 'div',
            __('li')     => 'li',
            __('no tag') => 'none',
        ];

        // Link combo
        $link_combo = [
            __('related posts')   => 'entry',
            __('original images') => 'image',
            __('no link')         => 'none',
        ];

        // Legend combo
        $legend_combo = [
            __('entry title') => 'entry',
            __('image title') => 'image',
            __('no legend')   => 'none',
        ];

        // Bubble combo
        $bubble_combo = [
            __('entry title') => 'entry',
            __('image title') => 'image',
            __('no bubble')   => 'none',
        ];

        // From combo
        $from_combo = [
            __('post excerpt') => 'excerpt',
            __('post content') => 'content',
            __('full post')    => 'full',
        ];

        // Alt combo
        $alt_combo = [
            __('image title') => 'inherit',
            __('no alt')      => 'none',
        ];

        /*
        * Admin page params.
        */
        App::backend()->from_combo     = $from_combo;
        App::backend()->img_size_combo = $img_size_combo;
        App::backend()->alt_combo      = $alt_combo;
        App::backend()->legend_combo   = $legend_combo;
        App::backend()->html_tag_combo = $html_tag_combo;
        App::backend()->link_combo     = $link_combo;
        App::backend()->bubble_combo   = $bubble_combo;

        // Saving configurations
        if (isset($_POST['save'])) {
            My::settings()->put('relatedEntries_enabled', !empty($_POST['relatedEntries_enabled']));
            My::settings()->put('relatedEntries_title', Html::escapeHTML($_POST['relatedEntries_title']));
            My::settings()->put('relatedEntries_beforePost', !empty($_POST['relatedEntries_beforePost']));
            My::settings()->put('relatedEntries_afterPost', !empty($_POST['relatedEntries_afterPost']));
            My::settings()->put('relatedEntries_images', !empty($_POST['relatedEntries_images']));

            $opts = [
                'size'     => !empty($_POST['size']) ? $_POST['size'] : 't',
                'html_tag' => !empty($_POST['html_tag']) ? $_POST['html_tag'] : 'div',
                'link'     => !empty($_POST['link']) ? $_POST['link'] : 'entry',
                'exif'     => 0,
                'legend'   => !empty($_POST['legend']) ? $_POST['legend'] : 'none',
                'bubble'   => !empty($_POST['bubble']) ? $_POST['bubble'] : 'image',
                'from'     => !empty($_POST['from']) ? $_POST['from'] : 'full',
                'start'    => !empty($_POST['start']) ? (int) $_POST['start'] : 1,
                'length'   => !empty($_POST['length']) ? (int) $_POST['length'] : 1,
                'class'    => !empty($_POST['class']) ? (string) $_POST['class'] : '',
                'alt'      => !empty($_POST['alt']) ? $_POST['alt'] : 'inherit',
                'img_dim'  => !empty($_POST['img_dim']) ? $_POST['img_dim'] : 0,
            ];

            My::settings()->put('relatedEntries_images_options', serialize($opts));

            Notices::addSuccessNotice(__('Configuration has been updated.'));

            App::blog()->triggerBlog();

            App::backend()->url()->redirect('admin.plugins', ['module' => My::id(), 'conf' => '1', 'redir' => $_REQUEST['redir']]);
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        echo Page::jsConfirmClose('module_config');

        $images = unserialize(My::settings()->relatedEntries_images_options);

        echo
        (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Activation'))))->fields([
                (new Para())->items([
                    (new Checkbox('relatedEntries_enabled', (bool) My::settings()->relatedEntries_enabled)),
                    (new Label(__('Enable related posts on this blog'), Label::OUTSIDE_LABEL_AFTER))
                        ->for('relatedEntries_enabled')
                        ->class('classic'),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Display options'))))->fields([
                (new Para())->items([
                    (new Input('relatedEntries_title'))
                        ->class('classic')
                        ->size(50)
                        ->maxlength(255)
                        ->value(My::settings()->relatedEntries_title)
                        ->label((new Label(
                            __('Block title:'),
                            Label::OUTSIDE_TEXT_BEFORE
                        ))),
                ]),
                (new Para())->items([
                    (new Checkbox('relatedEntries_beforePost', (bool) My::settings()->relatedEntries_beforePost)),
                    (new Label(__('Display block before post content'), Label::OUTSIDE_LABEL_AFTER))
                        ->for('relatedEntries_beforePost')
                        ->class('classic'),

                ]),
                (new Para())->items([
                    (new Checkbox('relatedEntries_afterPost', (bool) My::settings()->relatedEntries_afterPost)),
                    (new Label(__('Display block after post content'), Label::OUTSIDE_LABEL_AFTER))
                        ->for('relatedEntries_afterPost')
                        ->class('classic'),

                ]),
                (new Note())
                    ->class(['form-note', 'info', 'clear'])
                    ->text(__('Uncheck both boxes to use only the presentation widget.')),

            ]),
        ])
        ->render();

        if (App::plugins()->moduleExists('listImages')) {
            echo
            (new Div())->items([
                (new Fieldset())->class('fieldset')->legend((new Legend(__('Images extracting options'))))->fields([
                    (new Para())->items([
                        (new Checkbox('relatedEntries_images', (bool) My::settings()->relatedEntries_images)),
                        (new Label(__('Extract images from related posts'), Label::OUTSIDE_LABEL_AFTER))
                            ->for('relatedEntries_images')
                            ->class(['classic']),
                    ]),
                    (new Para())->items([
                        (new Select('from'))
                            ->items(App::backend()->from_combo)
                            ->default(($images['from'] != '' ? $images['from'] : 'image'))
                            ->label(new Label(__('Images origin:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),
                    (new Para())->items([
                        (new Select('size'))
                            ->items(App::backend()->img_size_combo)
                            ->default(($images['size'] != '' ? $images['size'] : 't'))
                            ->label(new Label(__('Image size:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),
                    (new Para())->items([
                        (new Checkbox('img_dim', (bool) $images['img_dim']))
                            ->label(new Label(__('Include images dimensions'), Label::INSIDE_LABEL_AFTER))
                            ->for('img_dim')
                            ->class(['classic']),
                    ]),
                    (new Para())->items([
                        (new Select('alt'))
                            ->items(App::backend()->alt_combo)
                            ->default(($images['alt'] != '' ? $images['alt'] : 'inherit'))
                            ->label(new Label(__('Images alt attribute:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),
                    (new Para())->items([
                        (new Input('start'))
                            ->type('number')
                            ->min(1)
                            ->max(1000)
                            ->size(3)
                            ->maxlength(3)
                            ->value($images['start'])
                            ->label(new Label(__('First image to extract:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),
                    (new Para())->items([
                        (new Input('length'))
                            ->type('number')
                            ->min(1)
                            ->max(1000)
                            ->size(3)
                            ->maxlength(3)
                            ->value($images['length'])
                            ->label(new Label(__('Number of images to extract:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),

                    (new Para())->items([
                        (new Select('legend'))
                        ->items(App::backend()->legend_combo)
                        ->default(($images['legend'] != '' ? $images['legend'] : 'none'))
                        ->label(new Label(__('Legend:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),
                    (new Para())->items([
                        (new Select('html_tag'))
                            ->items(App::backend()->html_tag_combo)
                            ->default(($images['html_tag'] != '' ? $images['html_tag'] : 'div'))
                            ->label(new Label(__('HTML tag around image:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),
                    (new Para())->items([
                        (new Input('class'))
                          ->class('classic')
                          ->size(10)
                          ->maxlength(255)
                          ->value($images['class'])
                          ->label(new Label(__('CSS class on images:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),
                    (new Para())->items([
                        (new Select('link'))
                            ->items(App::backend()->link_combo)
                            ->default(($images['link'] != '' ? $images['link'] : 'entry'))
                            ->label(new Label(__('Links destination:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),
                    (new Para())->items([
                        (new Select('bubble'))
                            ->items(App::backend()->bubble_combo)
                            ->default(($images['bubble'] != '' ? $images['bubble'] : 'image'))
                            ->label(new Label(__('Bubble:'), Label::OUTSIDE_LABEL_BEFORE)),
                    ]),

                ]),
            ])
            ->render();
        } else {
            echo
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Images extracting options'))))->fields([
                (new Para())
                    ->class(['form-note', 'info', 'clear'])
                    ->items([
                        (new Text('span', __('Install or activate listImages plugin to be able to display links to related entries as images'))),
                    ]),
            ])
            ->render();
        }

        Page::helpBlock('config');
    }
}
