<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

use Contao\DC_Table;
use Doctrine\DBAL\Platforms\MySQLPlatform;

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
        'virtualField' => [
            'saveTo' => 'virtualTarget',
        ],
        'virtualTarget' => [
            'virtualTarget' => true,
            'sql' => ['type' => 'json', 'length' => MySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull' => false],
        ]
    ],
];
