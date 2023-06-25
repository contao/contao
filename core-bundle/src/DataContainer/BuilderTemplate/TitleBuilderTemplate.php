<?php

namespace Contao\CoreBundle\DataContainer\BuilderTemplate;

use Contao\CoreBundle\DataContainer\AbstractDataContainerBuilderTemplate;
use Contao\DataContainer;

/** 
 * This DCA template adds a searchable and sortable "title" field
 * and a default palette.
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
            ],
            'fields' => [
                'title' => [
                    'search' => true,
                    'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
                    'inputType' => 'text',
                    'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => true],
                    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
                ],
            ],
            'palettes' => [
                'default' => '{title_legend},title'
            ],
        ];
    }
}
