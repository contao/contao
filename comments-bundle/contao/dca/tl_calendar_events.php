<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['headerFields'][] = 'allowComments';

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['noComments'] = array
(
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50 m12'),
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

PaletteManipulator::create()
    ->addField(['noComments'], 'expert_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar_events')
    ->applyToPalette('internal', 'tl_calendar_events')
    ->applyToPalette('article', 'tl_calendar_events')
    ->applyToPalette('external', 'tl_calendar_events')
;
