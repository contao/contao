<?php

$GLOBALS['TL_DCA']['tl_test_without_sql_config_without_driver'] = [
    'config' => [
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

?>
