<?php
/**
 * @brief Related Entries, a plugin for Dotclear 2
 *
 * @package    Dotclear
 * @subpackage Plugins
 *
 * @author Philippe aka amalgame
 *
 * @copyright GPL-2.0 [https://www.gnu.org/licenses/gpl-2.0.html]
 */

declare(strict_types=1);

namespace Dotclear\Plugin\relatedEntries;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Listing\Listing;
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Timestamp;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;

/**
 * @brief   Posts mini list pager form helper.
 *
 * @since   2.20
 */
class BackendMiniList extends Listing
{
    /**
     * Display a mini post list.
     *
     * @param   int     $page           The page
     * @param   int     $nb_per_page    The number of posts per page
     * @param   string  $enclose_block  The enclose block
     */
    public function display(int $page, int $nb_per_page, int $id, string $enclose_block = '', string $post_type = ''): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Para())
                ->items([
                    (new Strong(__('No links'))),
                ])
            ->render();

            return;
        }

        $pager   = (new Pager($page, (int) $this->rs_count, $nb_per_page, 10))->getLinks();
        $entries = [];
        if (isset($_REQUEST['entries'])) {
            foreach ($_REQUEST['entries'] as $v) {
                $entries[(int) $v] = true;
            }
        }

        $cols = [
            'title' => (new Th())
                ->scope('col')
                ->class('first')
                ->text(__('Title'))
            ->render(),

            'date' => (new Th())
                ->scope('col')
                ->text(__('Date'))
            ->render(),

            'category' => (new Th())
                ->scope('col')
                ->text(__('Category'))
            ->render(),

            'status' => (new Th())
                ->scope('col')
                ->text(__('Status'))
            ->render(),

            'actions' => (new Th())
                ->scope('col')
                ->class('last')
                ->text(__('Action'))
            ->render(),
        ];

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPostMiniListHeaderV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminPostMiniListHeaderV2', $this->rs, $cols);

        // Prepare listing

        $lines = [];
        $types = [];

        $count = 0;

        while ($this->rs->fetch()) {
            $lines[] = $this->postLine($id, $post_type);
            if (!in_array($this->rs->post_type, $types)) {
                $types[] = $this->rs->post_type;
            }
        }

        $buffer = (new Div())
                    ->class('table-outer')
                    ->items([
                        (new Table())
                            ->class(['maximal', 'dragable'])
                            ->caption(new Caption(sprintf(__('List of links (%s)'), $this->rs_count)))
                            ->items([
                                (new Thead())
                                    ->rows([
                                        (new Tr())
                                            ->items([
                                                (new Text(null, implode('', iterator_to_array($cols)))),
                                            ]),
                                    ]),
                                (new Tbody())
                                    ->id('pageslist')
                                    ->rows($lines),
                            ]),

                    ])
                ->render();

        if ($enclose_block !== '') {
            $buffer = sprintf($enclose_block, $buffer);
        }

        echo $buffer;
    }

    /**
     * Get a line.
     *
     * @param   bool    $checked    The checked flag
     */
    private function postLine(int $id, string $posttype = ''): Tr
    {
        $id = $_GET['id'];

        $post_classes = ['line'];
        if (App::status()->post()->isRestricted((int) $this->rs->post_status)) {
            $post_classes[] = 'offline';
        }
        $post_classes[] = 'sts-' . App::status()->post()->id((int) $this->rs->post_status); // used ?

        $status = [];

        switch ((int) $this->rs->post_status) {
            case App::status()->post()::PUBLISHED:
                $status[] = self::getMyRowImage(__('Published'), 'images/published.svg', 'published');

                break;
            case App::status()->post()::UNPUBLISHED:
                $status[] = self::getMyRowImage(__('Unpublished'), 'images/unpublished.svg', 'unpublished');

                break;
            case App::status()->post()::SCHEDULED:
                $status[] = self::getMyRowImage(__('Scheduled'), 'images/scheduled.svg', 'scheduled');

                break;
            case App::status()->post()::PENDING:
                $status[] = self::getMyRowImage(__('Pending'), 'images/pending.svg', 'pending');

                break;
        }

        if ($this->rs->post_selected) {
            $status[] = self::getMyRowImage(__('Selected'), 'images/selected.svg', 'selected');
        }

        $nb_media = $this->rs->countMedia();
        if ($nb_media > 0) {
            $status[] = self::getRowImage(sprintf($nb_media == 1 ? __('%d attachment') : __('%d attachments'), $nb_media), 'images/attach.svg', 'attach');
        }

        if ($this->rs->cat_title) {
            if (App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CATEGORIES,
            ]), App::blog()->id())) {
                $category = (new Link())
                    ->href(App::backend()->url()->get('admin.category', ['id' => $this->rs->cat_id], '&amp;', true))
                    ->text(Html::escapeHTML($this->rs->cat_title));
            } else {
                $category = (new Text(null, Html::escapeHTML($this->rs->cat_title)));
            }
        } else {
            $category = (new Text(null, __('(No cat)')));
        }

        $actions[] = self::getRowImage(__('Selected'), 'images/trash.svg', 'selected');

        $cols = [
            'title' => (new Td())
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href(App::postTypes()->get($this->rs->post_type)->adminUrl($this->rs->post_id))
                        ->title(Html::escapeHTML($this->rs->getURL()))
                        ->text(Html::escapeHTML(trim(Html::clean($this->rs->post_title)))),
                ])
            ->render(),
            'date' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Timestamp(Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt)))
                        ->datetime(Date::iso8601((int) strtotime($this->rs->post_dt), App::auth()->getInfo('user_tz'))),
                ])
            ->render(),

            'category' => (new Td())
                ->class('nowrap')
                ->items([
                    $category,
                ])
            ->render(),

            'status' => (new Td())
                ->class(['nowrap', 'status'])
                ->separator(' ')
                ->items([
                    ... $status,
                ])
            ->render(),

            'actions' => (new Td())
                ->class(['nowrap', 'count'])
                ->separator(' ')
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.plugin', ['p' => My::id(),'id' => $id, 'r_id' => $this->rs->post_id], '&'))
                        ->title(__('Delete this link') . ': ' . Html::escapeHTML($this->rs->post_title))
                        ->class(['mark', 'element-remove'])
                        ->items([
                            self::getMyRowImage(__('Delete this link') . ': ' . Html::escapeHTML($this->rs->post_title), 'images/trash.svg', 'remove'),
                        ]),
                ])
            ->render(),
        ];

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPostListValueV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminPostMiniListValueV2', $this->rs, $cols);

        return (new Tr())
            ->id('p' . $this->rs->post_id)
            ->class($post_classes)
            ->items([
                (new Text(null, implode('', iterator_to_array($cols)))),
            ]);
    }

    /**
     * Get image title and src for status icons
     *
     * @param string $title the image title
     * @param string $image the image source
     * @param string $class the class to apply to the image
     * @param bool   $with_text Whether to include the title as text next to the image
     * @return Img|Text
     */
    public static function getMyRowImage(string $title, string $image, string $class, bool $with_text = false): Img|Text
    {
        $img = (new Img($image))
            ->alt(Html::escapeHTML($title))
            ->class(['mark', 'mark-' . $class])
            ->title(Html::escapeHTML($title));

        return $with_text ?
            (new Text(null, $img->render() . ' ' . Html::escapeHTML($title))) :
            $img;
    }
}
