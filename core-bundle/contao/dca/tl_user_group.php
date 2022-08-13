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
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;

System::loadLanguageFile('tl_user');

$GLOBALS['TL_DCA']['tl_user_group'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'onload_callback' => array
		(
			array('tl_user_group', 'addTemplateWarning')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('name'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'             => 'filter;search,limit',
		),
		'label' => array
		(
			'fields'                  => array('name'),
			'format'                  => '%s',
			'label_callback'          => array('tl_user_group', 'addIcon')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},name;{modules_legend},modules,themes;{elements_legend},elements,fields;{pagemounts_legend},pagemounts,alpty;{filemounts_legend},filemounts,fop;{imageSizes_legend},imageSizes;{forms_legend},forms,formp;{amg_legend},amg;{alexf_legend:hide},alexf;{account_legend},disable,start,stop',
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'name' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'modules' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['modules'],
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_user_group', 'getModules'),
			'reference'               => &$GLOBALS['TL_LANG']['MOD'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true, 'collapseUncheckedGroups'=>true),
			'sql'                     => "blob NULL"
		),
		'themes' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['themes'],
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'options'                 => array('modules', 'layout', 'image_sizes', 'theme_import', 'theme_export'),
			'reference'               => &$GLOBALS['TL_LANG']['MOD'],
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'elements' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['elements'],
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_user_group', 'getContentElements'),
			'reference'               => &$GLOBALS['TL_LANG']['CTE'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true, 'collapseUncheckedGroups'=>true),
			'sql'                     => "blob NULL"
		),
		'fields' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['fields'],
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'options'                 => array_keys($GLOBALS['TL_FFL']),
			'reference'               => &$GLOBALS['TL_LANG']['FFL'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true),
			'sql'                     => "blob NULL"
		),
		'pagemounts' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['pagemounts'],
			'inputType'               => 'pageTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox'),
			'sql'                     => "blob NULL"
		),
		'alpty' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['alpty'],
			'default'                 => array('regular', 'redirect', 'forward'),
			'inputType'               => 'checkbox',
			'reference'               => &$GLOBALS['TL_LANG']['PTY'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true),
			'sql'                     => "blob NULL"
		),
		'filemounts' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['filemounts'],
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox'),
			'sql'                     => "blob NULL"
		),
		'fop' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['FOP']['fop'],
			'default'                 => array('f1', 'f2', 'f3'),
			'inputType'               => 'checkbox',
			'options'                 => array('f1', 'f2', 'f3', 'f4', 'f5', 'f6'),
			'reference'               => &$GLOBALS['TL_LANG']['FOP'],
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'imageSizes' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['imageSizes'],
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('multiple'=>true, 'collapseUncheckedGroups'=>true),
			'options_callback' => static function ()
			{
				return System::getContainer()->get('contao.image.sizes')->getAllOptions();
			},
			'sql'                     => "blob NULL"
		),
		'forms' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['forms'],
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_form.title',
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'formp' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['formp'],
			'inputType'               => 'checkbox',
			'options'                 => array('create', 'delete'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'amg' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['amg'],
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'alexf' => array
		(
			'search'                  => true,
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_user_group', 'getExcludedFields'),
			'eval'                    => array('multiple'=>true, 'size'=>36, 'collapseUncheckedGroups'=>true),
			'sql'                     => "blob NULL"
		),
		'disable' => array
		(
			'reverseToggle'           => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'start' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_user_group extends Backend
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
	 * Add a warning if there are users with access to the template editor.
	 */
	public function addTemplateWarning()
	{
		if (Input::get('act') && Input::get('act') != 'select')
		{
			return;
		}

		$objResult = $this->Database->query("SELECT EXISTS(SELECT * FROM tl_user_group WHERE modules LIKE '%\"tpl_editor\"%') as showTemplateWarning, EXISTS(SELECT * FROM tl_user_group WHERE themes LIKE '%\"theme_import\"%') as showThemeWarning, EXISTS(SELECT * FROM tl_user_group WHERE modules LIKE '%\"themes\"%' AND themes LIKE '%\"modules\"%' AND (alexf LIKE '%\"tl_module::list_table\"%' OR alexf LIKE '%\"tl_module::list_fields\"%' OR alexf LIKE '%\"tl_module::list_where\"%' OR alexf LIKE '%\"tl_module::list_search\"%' OR alexf LIKE '%\"tl_module::list_sort\"%' OR alexf LIKE '%\"tl_module::list_info\"%' OR alexf LIKE '%\"tl_module::list_info_where\"%')) as showListingWarning, EXISTS(SELECT * FROM tl_user_group WHERE alexf LIKE '%\"tl_content::unfilteredHtml\"%' OR alexf LIKE '%\"tl_module::unfilteredHtml\"%' OR elements LIKE '%\"unfiltered_html\"%') as showUnfilteredHtmlWarning");

		if ($objResult->showTemplateWarning > 0)
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['groupTemplateEditor']);
		}

		if ($objResult->showThemeWarning > 0)
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['groupThemeImport']);
		}

		if ($objResult->showListingWarning > 0)
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['groupListingModule']);
		}

		if ($objResult->showUnfilteredHtmlWarning > 0)
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['groupUnfilteredHtml']);
		}
	}

	/**
	 * Add an image to each record
	 *
	 * @param array  $row
	 * @param string $label
	 *
	 * @return string
	 */
	public function addIcon($row, $label)
	{
		$image = 'group';
		$disabled = ($row['start'] !== '' && $row['start'] > time()) || ($row['stop'] !== '' && $row['stop'] <= time());

		if ($disabled || $row['disable'])
		{
			$image .= '_';
		}

		return sprintf(
			'<div class="list_icon" style="background-image:url(\'%s\')" data-icon="%s" data-icon-disabled="%s">%s</div>',
			Controller::addStaticUrlTo(Image::getPath($image)),
			Controller::addStaticUrlTo(Image::getPath($disabled ? $image : rtrim($image, '_'))),
			Controller::addStaticUrlTo(Image::getPath(rtrim($image, '_') . '_')),
			$label
		);
	}

	/**
	 * Return all modules except profile modules
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getModules(DataContainer $dc)
	{
		$arrModules = array();

		foreach ($GLOBALS['BE_MOD'] as $k=>$v)
		{
			if (empty($v))
			{
				continue;
			}

			foreach ($v as $kk=>$vv)
			{
				if (isset($vv['disablePermissionChecks']) && $vv['disablePermissionChecks'] === true)
				{
					unset($v[$kk]);
				}
			}

			$arrModules[$k] = array_keys($v);
		}

		$modules = StringUtil::deserialize($dc->activeRecord->modules);

		// Unset the template editor unless the user is an administrator or has been granted access to the template editor
		if (!$this->User->isAdmin && (!is_array($modules) || !in_array('tpl_editor', $modules)) && ($key = array_search('tpl_editor', $arrModules['design'])) !== false)
		{
			unset($arrModules['design'][$key]);
			$arrModules['design'] = array_values($arrModules['design']);
		}

		return $arrModules;
	}

	/**
	 * Return all content elements
	 *
	 * @return array
	 */
	public function getContentElements()
	{
		return array_map('array_keys', $GLOBALS['TL_CTE']);
	}

	/**
	 * Return all excluded fields as HTML drop down menu
	 *
	 * @return array
	 */
	public function getExcludedFields()
	{
		$processed = array();

		/** @var SplFileInfo[] $files */
		$files = System::getContainer()->get('contao.resource_finder')->findIn('dca')->depth(0)->files()->name('*.php');

		foreach ($files as $file)
		{
			if (in_array($file->getBasename(), $processed))
			{
				continue;
			}

			$processed[] = $file->getBasename();

			$strTable = $file->getBasename('.php');

			System::loadLanguageFile($strTable);
			$this->loadDataContainer($strTable);
		}

		$arrReturn = array();

		// Get all excluded fields
		foreach ($GLOBALS['TL_DCA'] as $k=>$v)
		{
			if (is_array($v['fields']))
			{
				foreach ($v['fields'] as $kk=>$vv)
				{
					// Hide the "admin" field if the user is not an admin (see #184)
					if ($k == 'tl_user' && $kk == 'admin' && !$this->User->isAdmin)
					{
						continue;
					}

					if (DataContainer::isFieldExcluded($k, $kk))
					{
						$arrReturn[$k][StringUtil::specialchars($k . '::' . $kk)] = isset($vv['label'][0]) ? $vv['label'][0] . ' <span style="color:#999;padding-left:3px">[' . $kk . ']</span>' : $kk;
					}
				}
			}
		}

		ksort($arrReturn);

		return $arrReturn;
	}
}
