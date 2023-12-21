<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'allowComments';
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['allowComments'] = 'notify,sortOrder,perPage,moderate,bbcode,requireLogin,disableCaptcha';

$GLOBALS['TL_DCA']['tl_calendar']['fields']['allowComments'] = [
    'filter'                  => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('submitOnChange'=>true),
    'sql'                     => array('type' => 'boolean', 'default' => false)
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['notify'] = array
(
    'inputType'               => 'select',
    'options'                 => array('notify_admin', 'notify_author', 'notify_both'),
    'eval'                    => array('tl_class'=>'w50'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_calendar'],
    'sql'                     => "varchar(32) NOT NULL default 'notify_admin'"
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['sortOrder'] = array
(
    'inputType'               => 'select',
    'options'                 => array('ascending', 'descending'),
    'reference'               => &$GLOBALS['TL_LANG']['MSC'],
    'eval'                    => array('tl_class'=>'w50 clr'),
    'sql'                     => "varchar(32) NOT NULL default 'ascending'"
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['perPage'] = array
(
    'inputType'               => 'text',
    'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
    'sql'                     => "smallint(5) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['moderate'] = array
(
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['bbcode'] = array
(
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['requireLogin'] = array
(
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['disableCaptcha'] = array
(
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

// Extend the default palettes
PaletteManipulator::create()
    ->addLegend('comments_legend', 'protected_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField(['allowComments'], 'comments_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_calendar')
;
