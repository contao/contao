<?php

declare(ticks=1 , strict_types=1);

$GLOBALS['TL_DCA']['tl_test'] = [
    'config' => [
        'dataContainer' => Contao\DC_Table::class,
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
    ],
];

?>

