<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\BackendUser;
use Contao\CalendarBundle\Security\ContaoCalendarPermissions;
use Contao\Controller;
use Contao\DataContainer;
use Contao\System;

// Add a palette selector
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cal_format';

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['calendar']         = '{title_legend},name,headline,type;{config_legend},cal_calendar,cal_noSpan,cal_startDay,cal_featured;{redirect_legend},jumpTo;{template_legend:hide},cal_ctemplate,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['eventlist']        = '{title_legend},name,headline,type;{config_legend},cal_calendar,cal_noSpan,cal_format,cal_featured,cal_order,cal_readerModule,cal_limit,perPage,cal_ignoreDynamic,cal_hideRunning;{template_legend:hide},cal_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['eventreader']      = '{title_legend},name,headline,type;{config_legend},cal_calendar,cal_hideRunning,overviewPage,customLabel;{template_legend:hide},cal_template,customTpl;{image_legend},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['eventmenu']        = '{title_legend},name,headline,type;{config_legend},cal_calendar,cal_noSpan,cal_format,cal_featured,cal_order,cal_showQuantity;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['eventmenucal_day'] = '{title_legend},name,headline,type;{config_legend},cal_calendar,cal_noSpan,cal_format,cal_featured,cal_startDay,cal_showQuantity;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['cal_calendar'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options_callback'        => array('tl_module_calendar', 'getCalendars'),
	'eval'                    => array('mandatory'=>true, 'multiple'=>true),
	'sql'                     => "blob NULL",
	'relation'                => array('table'=>'tl_calendar', 'type'=>'hasMany', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_noSpan'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_hideRunning'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_startDay'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array(0, 1, 2, 3, 4, 5, 6),
	'reference'               => &$GLOBALS['TL_LANG']['DAYS'],
	'eval'                    => array('tl_class'=>'w50 clr'),
	'sql'                     => "smallint(5) unsigned NOT NULL default 1"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_format'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_calendar', 'getFormats'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50 clr', 'submitOnChange'=>true),
	'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'cal_month'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_ignoreDynamic'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'clr w50'),
	'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_order'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('ascending', 'descending'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default 'ascending'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_readerModule'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_calendar', 'getReaderModules'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default 0",
	'relation'                => array('table'=>'tl_module', 'type'=>'hasOne', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_limit'] = array
(
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
	'sql'                     => "smallint(5) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_template'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback' => static function ()
	{
		return Controller::getTemplateGroup('event_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_ctemplate'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback' => static function ()
	{
		return Controller::getTemplateGroup('cal_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_showQuantity'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50 m12'),
	'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cal_featured'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('all_items', 'featured', 'unfeatured'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module']['events'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default 'all_items'"
);

$bundles = System::getContainer()->getParameter('kernel.bundles');

// Add the comments template drop-down menu
if (isset($bundles['ContaoCommentsBundle']))
{
	$GLOBALS['TL_DCA']['tl_module']['palettes']['eventreader'] = str_replace('{protected_legend:hide}', '{comment_legend:hide},com_template;{protected_legend:hide}', $GLOBALS['TL_DCA']['tl_module']['palettes']['eventreader']);
}

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_module_calendar extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(BackendUser::class, 'User');
	}

	/**
	 * Get all calendars and return them as array
	 *
	 * @return array
	 */
	public function getCalendars()
	{
		if (!$this->User->isAdmin && !is_array($this->User->calendars))
		{
			return array();
		}

		$arrCalendars = array();
		$objCalendars = $this->Database->execute("SELECT id, title FROM tl_calendar ORDER BY title");
		$security = System::getContainer()->get('security.helper');

		while ($objCalendars->next())
		{
			if ($security->isGranted(ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR, $objCalendars->id))
			{
				$arrCalendars[$objCalendars->id] = $objCalendars->title;
			}
		}

		return $arrCalendars;
	}

	/**
	 * Get all event reader modules and return them as array
	 *
	 * @return array
	 */
	public function getReaderModules()
	{
		$arrModules = array();
		$objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='eventreader' ORDER BY t.name, m.name");

		while ($objModules->next())
		{
			$arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
		}

		return $arrModules;
	}

	/**
	 * Return the calendar formats depending on the module type
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getFormats(DataContainer $dc)
	{
		if ($dc->activeRecord->type == 'eventmenu')
		{
			return array('cal_day', 'cal_month', 'cal_year');
		}

		return array
		(
			'cal_list'     => array('cal_day', 'cal_month', 'cal_year', 'cal_all'),
			'cal_upcoming' => array('next_7', 'next_14', 'next_30', 'next_90', 'next_180', 'next_365', 'next_two', 'next_cur_month', 'next_cur_year', 'next_next_month', 'next_next_year', 'next_all'),
			'cal_past'     => array('past_7', 'past_14', 'past_30', 'past_90', 'past_180', 'past_365', 'past_two', 'past_cur_month', 'past_cur_year', 'past_prev_month', 'past_prev_year', 'past_all')
		);
	}
}
