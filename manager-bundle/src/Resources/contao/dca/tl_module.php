<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Config;

// Palettes
$GLOBALS['TL_DCA']['tl_module']['palettes']['maintenance_login'] = '{title_legend},name,headline,type;{config_legend},maintenanceUsername,maintenancePassword;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Fields
$GLOBALS['TL_DCA']['tl_module']['fields'] += array
(
	'maintenanceUsername' => array
	(
		'exclude'                 => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'rgxp'=>'extnd', 'nospace'=>true, 'unique'=>true, 'maxlength'=>64, 'tl_class'=>'w50', 'autocapitalize'=>'off', 'autocomplete'=>'username'),
		'sql'                     => "varchar(64) BINARY NULL"
	),
	'maintenancePassword' => array
	(
		'exclude'                 => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'preserveTags'=>true, 'minlength'=>Config::get('minPasswordLength'), 'maxlength'=>64, 'hideInput'=>true, 'tl_class'=>'w50'),
		'sql'                     => "varchar(64) NOT NULL default ''"
	),
);
