<?php

namespace Contao\CoreBundle\DataContainer\BuilderTemplate;

use Contao\CoreBundle\DataContainer\AbstractDataContainerBuilderTemplate;
use Contao\DataContainer;
use Contao\DC_Table;

/** 
 * Provides defaults for a DC_Table based data container.
 */
class DatabaseDefaultBuilderTemplate extends AbstractDataContainerBuilderTemplate
{
    public function getConfig(): array
    {
        return [
            'config' => [
                'dataContainer' => DC_Table::class,
                'sql' => [
                    'keys' => [
                        'id' => DataContainer::INDEX_PRIMARY,
                    ],
                ],
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_UNSORTED,
                    'panelLayout' => 'filter;search,limit',
                ],
                'global_operations' => [
                    'all' => [
                        'href' => 'act=select',
                        'class' => 'header_edit_all',
                        'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
                    ],
                ],
                'operations' => [
                    'edit',
                    'delete',
                    'show',
                ],
            ],
            'fields' => [
                'id' => [
                    'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
                ],
                'tstamp' => [
                    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
                ],
            ],
        ];
    }
}
