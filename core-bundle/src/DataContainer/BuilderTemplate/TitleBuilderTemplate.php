<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer\BuilderTemplate;

use Contao\DataContainer;

/**
 * Adds a searchable and sortable "title" field and a default palette.
 */
class TitleBuilderTemplate extends AbstractDataContainerBuilderTemplate
{
    public function getConfig(): array
    {
        return [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTABLE,
                    'fields' => ['title'],
                ],
                'label' => [
                    'fields' => ['title'],
                    'format' => '%s',
                ],
            ],
            'fields' => [
                'title' => [
                    'exclude' => true,
                    'search' => true,
                    'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
                    'inputType' => 'text',
                    'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => true],
                    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
                ],
            ],
            'palettes' => [
                'default' => '{title_legend},title',
            ],
        ];
    }
}
