<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_faq_category']['palettes']['__selector__'][] = 'allowComments';
$GLOBALS['TL_DCA']['tl_faq_category']['subpalettes']['allowComments'] = 'notify,sortOrder,perPage,moderate,bbcode,requireLogin,disableCaptcha';

$GLOBALS['TL_DCA']['tl_faq_category']['fields']['allowComments'] = [
    'filter'                  => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('submitOnChange'=>true),
    'sql'                     => array('type' => 'boolean', 'default' => false)
];

$GLOBALS['TL_DCA']['tl_faq_category']['fields']['notify'] = array
(
    'inputType'               => 'select',
    'options'                 => array('notify_admin', 'notify_author', 'notify_both'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_faq_category'],
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "varchar(16) NOT NULL default 'notify_admin'"
);

$GLOBALS['TL_DCA']['tl_faq_category']['fields']['sortOrder'] = array
(
    'inputType'               => 'select',
    'options'                 => array('ascending', 'descending'),
    'reference'               => &$GLOBALS['TL_LANG']['MSC'],
    'eval'                    => array('tl_class'=>'w50 clr'),
    'sql'                     => "varchar(12) NOT NULL default 'ascending'"
);

$GLOBALS['TL_DCA']['tl_faq_category']['fields']['perPage'] = array
(
    'inputType'               => 'text',
    'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
    'sql'                     => "smallint(5) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_faq_category']['fields']['moderate'] = array
(
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_faq_category']['fields']['bbcode'] = array
(
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_faq_category']['fields']['requireLogin'] = array
(
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_faq_category']['fields']['disableCaptcha'] = array
(
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => array('type' => 'boolean', 'default' => false)
);

// Extend the default palettes
PaletteManipulator::create()
    ->addLegend('comments_legend', 'title_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField(['allowComments'], 'comments_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_faq_category')
;
