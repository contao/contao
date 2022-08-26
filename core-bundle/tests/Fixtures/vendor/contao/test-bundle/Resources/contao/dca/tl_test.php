<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_test'] = [
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
    ],
];
