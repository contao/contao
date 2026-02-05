<?php

use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_test_with_class'] = [
    'config' => [
        'dataContainer' => Contao\DC_Table::class,
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
    ],
];

class tl_test_with_classes1
{
    public function checkPermission(DataContainer $dc)
    {
    }
}

class tl_test_with_classes2
{
    public function checkPermission(DataContainer $dc)
    {
    }
}
