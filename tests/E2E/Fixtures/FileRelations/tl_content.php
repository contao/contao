<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_content']['fields']['file_rows'] = [
    'label' => ['Files', ''],
    'inputType' => 'rowWizard',
    'exclude' => false,
    'fields' => [
        'single' => [
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio'],
        ],
        'multiple' => [
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'checkbox', 'multiple' => true],
        ],
    ],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

PaletteManipulator::create()
    ->addField('file_rows', 'text_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('text', 'tl_content')
;
