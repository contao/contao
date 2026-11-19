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

// A JSON column with a virtual select field without a default, like the style
// options of a theme extension
$GLOBALS['TL_DCA']['tl_article']['fields']['virtual_styles'] = [
    'virtualTarget' => true,
    'sql' => ['type' => 'json', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_article']['fields']['virtual_width'] = [
    'label' => ['Virtual width', ''],
    'inputType' => 'select',
    'options' => ['narrow', 'wide'],
    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'targetColumn' => 'virtual_styles',
];

PaletteManipulator::create()
    ->addField('virtual_width', 'layout_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_article')
;
