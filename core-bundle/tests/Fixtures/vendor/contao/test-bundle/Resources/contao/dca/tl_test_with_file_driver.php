<?php

$GLOBALS['TL_DCA']['tl_test_with_file_driver'] = [
    'config' => [
        'dataContainer' => Contao\DC_File::class,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
    ],
];
