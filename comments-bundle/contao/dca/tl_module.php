<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\System;

System::loadLanguageFile('tl_content');

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['comments'] = '{title_legend},name,headline,type;{comment_legend},com_order,perPage,com_moderate,com_bbcode,com_protected,com_requireLogin,com_disableCaptcha;{template_legend:hide},com_template,customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['com_order'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['com_order'],
	'inputType'               => 'select',
	'options'                 => array('ascending', 'descending'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default 'ascending'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['com_moderate'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['com_moderate'],
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_module']['fields']['com_bbcode'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['com_bbcode'],
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_module']['fields']['com_requireLogin'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['com_requireLogin'],
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_module']['fields']['com_disableCaptcha'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['com_disableCaptcha'],
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_module']['fields']['com_template'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['com_template'],
	'inputType'               => 'select',
	'options_callback' => static function () {
		return Controller::getTemplateGroup('com_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);

$bundles = System::getContainer()->getParameter('kernel.bundles');

if (isset($bundles['ContaoNewsBundle']))
{
    PaletteManipulator::create()
        ->addLegend('comment_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE, true)
        ->addField('com_template', 'comment_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToPalette('newsreader', 'tl_module')
    ;
}

if (isset($bundles['ContaoFaqBundle']))
{
    PaletteManipulator::create()
        ->addLegend('comment_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE, true)
        ->addField('com_template', 'comment_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToPalette('faqreader', 'tl_module')
    ;
}

if (isset($bundles['ContaoCalendarBundle']))
{
    PaletteManipulator::create()
        ->addLegend('comment_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE, true)
        ->addField('com_template', 'comment_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToPalette('eventreader', 'tl_module')
    ;
}
