<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_test_with_enums'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'foo' => [
            'enum' => \Contao\CoreBundle\Tests\Fixtures\Enum\StringBackedEnum::class,
        ],
        'bar' => [
            'enum' => \Contao\CoreBundle\Tests\Fixtures\Enum\IntBackedEnum::class,
        ],
    ],
];
