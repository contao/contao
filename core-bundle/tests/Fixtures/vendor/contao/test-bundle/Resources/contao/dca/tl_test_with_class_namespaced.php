<?php

namespace Foo\Bar;

use Contao;
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

class tl_test_with_class_namespaced
{
    public function checkPermission(DataContainer $dc)
    {
    }
}
