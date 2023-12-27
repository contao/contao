<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

#[AsHook('loadDataContainer')]
class CommentsConditionalFieldsListener
{
    public function __construct(private readonly array $bundles)
    {
    }

    public function __invoke(string $table): void
    {
        if (!isset($GLOBALS['TL_DCA'][$table])) {
            return;
        }

        switch ($table) {
            case 'tl_module':
                $this->applyModuleFields();
                break;

            case 'tl_news_archive':
            case 'tl_calendar':
            case 'tl_faq_category':
                $this->applyParentFields($table);
                break;

            case 'tl_news':
            case 'tl_calendar_events':
            case 'tl_faq':
                $this->applyChildrenFields($table);
                break;
        }
    }

    private function applyModuleFields(): void
    {
        $pm = PaletteManipulator::create()
            ->addLegend('comment_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE, true)
            ->addField('com_template', 'comment_legend', PaletteManipulator::POSITION_APPEND)
        ;

        if (isset($this->bundles['ContaoNewsBundle'])) {
            $pm->applyToPalette('newsreader', 'tl_module');
        }

        if (isset($this->bundles['ContaoFaqBundle'])) {
            $pm->applyToPalette('faqreader', 'tl_module');
        }

        if (isset($this->bundles['ContaoCalendarBundle'])) {
            $pm->applyToPalette('eventreader', 'tl_module');
        }
    }

    private function applyParentFields(string $table): void
    {
        $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'][] = 'allowComments';
        $GLOBALS['TL_DCA'][$table]['subpalettes']['allowComments'] = 'notify,sortOrder,perPage,moderate,bbcode,requireLogin,disableCaptcha';

        $GLOBALS['TL_DCA'][$table]['fields']['allowComments'] = [
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ];

        $GLOBALS['TL_DCA'][$table]['fields']['notify'] = [
            'inputType' => 'select',
            'options' => ['notify_admin', 'notify_author', 'notify_both'],
            'reference' => &$GLOBALS['TL_LANG'][$table],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default 'notify_admin'",
        ];

        $GLOBALS['TL_DCA'][$table]['fields']['sortOrder'] = [
            'inputType' => 'select',
            'options' => ['ascending', 'descending'],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => "varchar(32) NOT NULL default 'ascending'",
        ];

        $GLOBALS['TL_DCA'][$table]['fields']['perPage'] = [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 0',
        ];

        $GLOBALS['TL_DCA'][$table]['fields']['moderate'] = [
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ];

        $GLOBALS['TL_DCA'][$table]['fields']['bbcode'] = [
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ];

        $GLOBALS['TL_DCA'][$table]['fields']['requireLogin'] = [
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ];

        $GLOBALS['TL_DCA'][$table]['fields']['disableCaptcha'] = [
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ];

        PaletteManipulator::create()
            ->addLegend('comments_legend', null, PaletteManipulator::POSITION_APPEND, true)
            ->addField('allowComments', 'comments_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('default', $table)
        ;
    }

    private function applyChildrenFields(string $table): void
    {
        $GLOBALS['TL_DCA'][$table]['list']['sorting']['headerFields'][] = 'allowComments';

        $GLOBALS['TL_DCA'][$table]['fields']['noComments'] =
            [
                'filter' => true,
                'inputType' => 'checkbox',
                'sql' => ['type' => 'boolean', 'default' => false],
            ];

        $pm = PaletteManipulator::create()
            ->addLegend('expert_legend', 'publish_legend', PaletteManipulator::POSITION_BEFORE, true)
            ->addField('noComments', 'expert_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('default', $table)
        ;

        if ('tl_news' === $table || 'tl_calendar_events' === $table) {
            $GLOBALS['TL_DCA'][$table]['fields']['noComments']['eval'] = ['tl_class' => 'w50 m12'];

            $pm
                ->applyToPalette('internal', $table)
                ->applyToPalette('article', $table)
                ->applyToPalette('external', $table)
            ;
        }
    }
}
