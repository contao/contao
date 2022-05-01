<?php

$GLOBALS['TL_DCA']['tl_test_without_sql_config'] = [
    'config' => [
        'dataContainer' => Contao\DC_Table::class,
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
    ],
];
