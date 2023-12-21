<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_faq']['list']['sorting']['headerFields'][] = 'allowComments';

$GLOBALS['TL_DCA']['tl_faq']['fields']['noComments'] = array
(
    'filter'                  => true,
    'inputType'               => 'checkbox',
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

PaletteManipulator::create()
    ->addLegend('expert_legend', 'publish_legend', PaletteManipulator::POSITION_BEFORE, true)
    ->addField(['noComments'], 'expert_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_faq')
;
