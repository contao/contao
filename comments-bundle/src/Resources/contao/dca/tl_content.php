<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Controller;

// Add palette to tl_content
$GLOBALS['TL_DCA']['tl_content']['palettes']['comments'] = '{type_legend},type,headline;{comment_legend},com_order,com_perPage,com_moderate,com_bbcode,com_requireLogin,com_disableCaptcha;{template_legend:hide},com_template,customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

// Add fields to tl_content
$GLOBALS['TL_DCA']['tl_content']['fields']['com_order'] = array
(
	'inputType'               => 'select',
	'options'                 => array('ascending', 'descending'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default 'ascending'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['com_perPage'] = array
(
	'inputType'               => 'text',
	'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
	'sql'                     => "smallint(5) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['com_moderate'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_content']['fields']['com_bbcode'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_content']['fields']['com_disableCaptcha'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_content']['fields']['com_requireLogin'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_content']['fields']['com_template'] = array
(
	'inputType'               => 'select',
	'options_callback' => static function ()
	{
		return Controller::getTemplateGroup('com_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);
