<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ArrayUtil;
use Contao\Backend;
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_layout'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_theme',
		'enableVersioning'            => true,
		'markAsCopy'                  => 'name',
		'onload_callback' => array
		(
			array('tl_layout', 'checkPermission'),
			array('tl_layout', 'addCustomLayoutSectionReferences')
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
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('name'),
			'panelLayout'             => 'filter;sort,search,limit',
			'headerFields'            => array('name', 'author', 'tstamp'),
			'child_record_callback'   => array('tl_layout', 'listLayout')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('rows', 'cols', 'addJQuery', 'addMooTools', 'static'),
		'default'                     => '{title_legend},name;{header_legend},rows;{column_legend},cols;{sections_legend:hide},sections;{image_legend:hide},lightboxSize,defaultImageDensities;{style_legend},framework,stylesheet,external,loadingOrder,combineScripts;{modules_legend},modules;{script_legend},scripts,analytics,externalJs,script;{jquery_legend:hide},addJQuery;{mootools_legend:hide},addMooTools;{static_legend:hide},static;{expert_legend:hide},template,minifyMarkup,viewport,titleTag,cssClass,onload,head'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'rows_2rwh'                   => 'headerHeight',
		'rows_2rwf'                   => 'footerHeight',
		'rows_3rw'                    => 'headerHeight,footerHeight',
		'cols_2cll'                   => 'widthLeft',
		'cols_2clr'                   => 'widthRight',
		'cols_3cl'                    => 'widthLeft,widthRight',
		'addJQuery'                   => 'jquery',
		'addMooTools'                 => 'mootools',
		'static'                      => 'width,align'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_theme.name',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'name' => array
		(
			'inputType'               => 'text',
			'exclude'                 => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'rows' => array
		(
			'exclude'                 => true,
			'inputType'               => 'radioTable',
			'options'                 => array('1rw', '2rwh', '2rwf', '3rw'),
			'eval'                    => array('helpwizard'=>true, 'cols'=>4, 'submitOnChange'=>true),
			'reference'               => &$GLOBALS['TL_LANG']['tl_layout'],
			'sql'                     => "varchar(8) NOT NULL default '2rwh'"
		),
		'headerHeight' => array
		(
			'exclude'                 => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('px', '%', 'em', 'rem', 'vw', 'vh'),
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'footerHeight' => array
		(
			'exclude'                 => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('px', '%', 'em', 'rem', 'vw', 'vh'),
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'cols' => array
		(
			'exclude'                 => true,
			'inputType'               => 'radioTable',
			'options'                 => array('1cl', '2cll', '2clr', '3cl'),
			'eval'                    => array('helpwizard'=>true, 'cols'=>4, 'submitOnChange'=>true),
			'reference'               => &$GLOBALS['TL_LANG']['tl_layout'],
			'sql'                     => "varchar(8) NOT NULL default '2cll'"
		),
		'widthLeft' => array
		(
			'exclude'                 => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('px', '%', 'em', 'rem', 'vw', 'vh'),
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'widthRight' => array
		(
			'exclude'                 => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('px', '%', 'em', 'rem', 'vw', 'vh'),
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'sections' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'sectionWizard',
			'sql'                     => "blob NULL"
		),
		'framework' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkboxWizard',
			'options'                 => array('layout.css', 'responsive.css', 'grid.css', 'reset.css', 'form.css', 'icons.css'),
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true),
			'reference'               => &$GLOBALS['TL_LANG']['tl_layout'],
			'save_callback' => array
			(
				array('tl_layout', 'checkFramework')
			),
			'sql'                     => "varchar(255) NOT NULL default 'a:2:{i:0;s:10:\"layout.css\";i:1;s:14:\"responsive.css\";}'"
		),
		'stylesheet' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkboxWizard',
			'foreignKey'              => 'tl_style_sheet.name',
			'options_callback'        => array('tl_layout', 'getStyleSheets'),
			'eval'                    => array('multiple'=>true),
			'xlabel' => array
			(
				array('tl_layout', 'styleSheetLink')
			),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'external' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'extensions'=>'css,scss,less', 'isSortable'=>true),
			'sql'                     => "blob NULL"
		),
		'loadingOrder' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('external_first', 'internal_first'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_layout'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(16) NOT NULL default 'external_first'"
		),
		'combineScripts' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default '1'"
		),
		'modules' => array
		(
			'default'                 => array(array('mod'=>0, 'col'=>'main', 'enable'=>1)),
			'exclude'                 => true,
			'inputType'               => 'moduleWizard',
			'sql'                     => "blob NULL"
		),
		'template' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'select',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('fe_');
			},
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'minifyMarkup' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default '1'"
		),
		'lightboxSize' => array
		(
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'defaultImageDensities' => array
		(
			'inputType'               => 'text',
			'explanation'             => 'imageSizeDensities',
			'exclude'                 => true,
			'eval'                    => array('helpwizard'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'viewport' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'titleTag' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'placeholder'=>'{{page::pageTitle}} - {{page::rootPageTitle}}', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'cssClass' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'onload' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'head' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px', 'preserveTags'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'addJQuery' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'jquery' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'search'                  => true,
			'inputType'               => 'checkboxWizard',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('j_');
			},
			'eval'                    => array('multiple'=>true),
			'sql'                     => "text NULL"
		),
		'addMooTools' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'mootools' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'search'                  => true,
			'inputType'               => 'checkboxWizard',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('moo_');
			},
			'eval'                    => array('multiple'=>true),
			'sql'                     => "text NULL"
		),
		'analytics' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'checkboxWizard',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('analytics_');
			},
			'eval'                    => array('multiple'=>true),
			'sql'                     => "text NULL"
		),
		'externalJs' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'extensions'=>'js', 'isSortable'=>true),
			'sql'                     => "blob NULL"
		),
		'scripts' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'checkboxWizard',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('js_');
			},
			'eval'                    => array('multiple'=>true),
			'sql'                     => "text NULL"
		),
		'script' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:120px', 'preserveTags'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'static' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'width' => array
		(
			'exclude'                 => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('px', '%', 'em', 'rem', 'vw', 'vh'),
			'eval'                    => array('includeBlankOption'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'align' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('left', 'center', 'right'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default 'center'"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_layout extends Backend
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
	 * Check permissions to edit the table
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS))
		{
			throw new AccessDeniedException('Not enough permissions to access the page layout module.');
		}
	}

	/**
	 * Return all style sheets of the current theme
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getStyleSheets(DataContainer $dc)
	{
		$intPid = $dc->activeRecord->pid ?? null;

		if (Input::get('act') == 'overrideAll')
		{
			$intPid = Input::get('id');
		}

		$objStyleSheet = $this->Database->prepare("SELECT id, name FROM tl_style_sheet WHERE pid=?")
										->execute($intPid);

		if ($objStyleSheet->numRows < 1)
		{
			return array();
		}

		$return = array();

		while ($objStyleSheet->next())
		{
			$return[$objStyleSheet->id] = $objStyleSheet->name;
		}

		return $return;
	}

	/**
	 * List a page layout
	 *
	 * @param array $row
	 *
	 * @return string
	 */
	public function listLayout($row)
	{
		return '<div class="tl_content_left">' . $row['name'] . '</div>';
	}

	/**
	 * Add a link to edit the stylesheets of the theme
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function styleSheetLink(DataContainer $dc)
	{
		$href = System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_style_sheet', 'id'=>$dc->activeRecord->pid, 'popup'=>'1', 'nb'=>'1', 'rt'=>REQUEST_TOKEN));

		return ' <a href="' . StringUtil::specialcharsUrl($href) . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['tl_layout']['edit_styles']) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['tl_layout']['edit_styles'])) . '\',\'url\':this.href});return false">' . Image::getHtml('edit.svg') . '</a>';
	}

	/**
	 * Auto-select layout.css if responsive.css is selected (see #8222)
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function checkFramework($value)
	{
		if (empty($value))
		{
			return '';
		}

		$array = StringUtil::deserialize($value);

		if (empty($array) || !is_array($array))
		{
			return $value;
		}

		if (($i = array_search('responsive.css', $array)) !== false && !in_array('layout.css', $array))
		{
			ArrayUtil::arrayInsert($array, $i, 'layout.css');
		}

		return serialize($array);
	}
}
