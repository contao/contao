<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\DC_Table;

#[AsCallback(table: 'tl_content', target: 'config.onload')]
class ThemeElementViewListener
{
    public function __invoke(DC_Table $dc): void
    {
        if ('tl_theme' !== $dc->parentTable) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'defaultSearchField' => 'title',
            'headerFields' => ['name', 'author', 'tstamp'],
        ];

        $GLOBALS['TL_DCA']['tl_content']['list']['label'] = [
            'fields' => ['title', 'type'],
            'format' => '%s <span class="label-info">[%s]</span>',
            'group_callback' => static fn ($group, $mode, $field, $row) => 'type' === $field ? $row['type'] : $group,
        ];

        $GLOBALS['TL_DCA']['tl_content']['fields']['title']['eval']['mandatory'] = true;
    }
}
