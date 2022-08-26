<?php

$GLOBALS['TL_DCA']['tl_test_with_non_database_assisted_folder_driver'] = [
    'config' => [
        'dataContainer' => Contao\DC_Folder::class,
        'databaseAssisted' => false,
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
