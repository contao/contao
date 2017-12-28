<?php

declare(    strict_types=1    );

$GLOBALS['TL_DCA']['tl_test'] = [
    'config' => [
        'dataContainer' => 'DC_Table',
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
