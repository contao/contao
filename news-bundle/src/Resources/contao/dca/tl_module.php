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
use Contao\Controller;
use Contao\DataContainer;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Contao\System;

// Add a palette selector
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'news_format';

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['newslist']         = '{title_legend},name,headline,type;{config_legend},news_archives,news_readerModule,numberOfItems,news_featured,news_order,skipFirst,perPage;{template_legend:hide},news_metaFields,news_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['newsreader']       = '{title_legend},name,headline,type;{config_legend},news_archives,overviewPage,customLabel;{template_legend:hide},news_metaFields,news_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['newsarchive']      = '{title_legend},name,headline,type;{config_legend},news_archives,news_readerModule,news_format,news_order,news_jumpToCurrent,perPage;{template_legend:hide},news_metaFields,news_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['newsmenu']         = '{title_legend},name,headline,type;{config_legend},news_archives,news_showQuantity,news_format,news_order;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['newsmenunews_day'] = '{title_legend},name,headline,type;{config_legend},news_archives,news_showQuantity,news_format,news_startDay;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['news_archives'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options_callback'        => array('tl_module_news', 'getNewsArchives'),
	'eval'                    => array('multiple'=>true, 'mandatory'=>true),
	'sql'                     => "blob NULL",
	'relation'                => array('table'=>'tl_news_archive', 'type'=>'hasMany', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['news_featured'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('all_items', 'featured', 'unfeatured', 'featured_first'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50 clr'),
	'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default 'all_items'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['news_jumpToCurrent'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('hide_module', 'show_current', 'all_items'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['news_readerModule'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_news', 'getReaderModules'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['news_metaFields'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('date', 'author', 'comments'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "varchar(255) COLLATE ascii_bin NOT NULL default 'a:2:{i:0;s:4:\"date\";i:1;s:6:\"author\";}'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['news_template'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback' => static function ()
	{
		return Controller::getTemplateGroup('news_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['news_format'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('news_day', 'news_month', 'news_year'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50 clr', 'submitOnChange'=>true),
	'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'news_month'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['news_startDay'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array(0, 1, 2, 3, 4, 5, 6),
	'reference'               => &$GLOBALS['TL_LANG']['DAYS'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "smallint(5) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['news_order'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_news', 'getSortingOptions'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'order_date_desc'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['news_showQuantity'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
);

$bundles = System::getContainer()->getParameter('kernel.bundles');

// Add the comments template drop-down menu
if (isset($bundles['ContaoCommentsBundle']))
{
	$GLOBALS['TL_DCA']['tl_module']['palettes']['newsreader'] = str_replace('{protected_legend:hide}', '{comment_legend:hide},com_template;{protected_legend:hide}', $GLOBALS['TL_DCA']['tl_module']['palettes']['newsreader']);
}

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_module_news extends Backend
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
	 * Get all news archives and return them as array
	 *
	 * @return array
	 */
	public function getNewsArchives()
	{
		if (!$this->User->isAdmin && !is_array($this->User->news))
		{
			return array();
		}

		$arrArchives = array();
		$objArchives = $this->Database->execute("SELECT id, title FROM tl_news_archive ORDER BY title");
		$security = System::getContainer()->get('security.helper');

		while ($objArchives->next())
		{
			if ($security->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, $objArchives->id))
			{
				$arrArchives[$objArchives->id] = $objArchives->title;
			}
		}

		return $arrArchives;
	}

	/**
	 * Get all news reader modules and return them as array
	 *
	 * @return array
	 */
	public function getReaderModules()
	{
		$arrModules = array();
		$objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='newsreader' ORDER BY t.name, m.name");

		while ($objModules->next())
		{
			$arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
		}

		return $arrModules;
	}

	/**
	 * Return the sorting options
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getSortingOptions(DataContainer $dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->type == 'newsmenu')
		{
			return array('order_date_asc', 'order_date_desc');
		}

		return array('order_date_asc', 'order_date_desc', 'order_headline_asc', 'order_headline_desc', 'order_random');
	}
}
