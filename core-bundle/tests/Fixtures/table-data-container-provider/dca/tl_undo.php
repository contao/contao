<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_undo'] =
[
    'config' => [
        'dataContainer' => DC_Table::class,
        'backendSearchIgnore' => true,
    ],

    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'data' => [
            'search' => true,
        ],
    ],
];
