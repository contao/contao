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

$GLOBALS['TL_DCA']['tl_content']['fields']['row_wizard_basic'] = [
    'label' => ['Basic row wizard', ''],
    'inputType' => 'rowWizard',
    'fields' => [
        'name' => [
            'label' => ['Name', ''],
            'inputType' => 'text',
        ],
        'number' => [
            'label' => ['Number', ''],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural'],
        ],
        'type' => [
            'label' => ['Type', ''],
            'inputType' => 'select',
            'options' => ['a', 'b', 'c'],
        ],
    ],
    'eval' => ['actions' => ['copy', 'delete', 'enable'], 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['row_wizard_with_min'] = [
    'label' => ['Row wizard with min', ''],
    'inputType' => 'rowWizard',
    'fields' => [
        'name' => [
            'label' => ['Name', ''],
            'inputType' => 'text',
        ],
    ],
    'eval' => ['min' => 3, 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['row_wizard_with_max'] = [
    'label' => ['Row wizard with max', ''],
    'inputType' => 'rowWizard',
    'fields' => [
        'name' => [
            'label' => ['Name', ''],
            'inputType' => 'text',
        ],
    ],
    'eval' => ['max' => 3, 'tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['row_wizard_with_limited_actions'] = [
    'label' => ['Limited row wizard', ''],
    'inputType' => 'rowWizard',
    'fields' => [
        'name' => [
            'label' => ['Name', ''],
            'inputType' => 'text',
        ],
    ],
    'eval' => ['actions' => ['copy', 'edit'], 'sortable' => false, 'tl_class' => 'clr'],
];

PaletteManipulator::create()
    ->addField(['row_wizard_basic', 'row_wizard_with_min', 'row_wizard_with_max', 'row_wizard_with_limited_actions'], 'text_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('text', 'tl_content')
;
