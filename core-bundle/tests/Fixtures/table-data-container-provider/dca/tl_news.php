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

$GLOBALS['TL_DCA']['tl_news'] =
[
    'config' => [
        'dataContainer' => DC_Table::class,
    ],

    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'headline' => [
            'search' => true,
        ],
        'teaser' => [
            'search' => true,
        ],
    ],
];
