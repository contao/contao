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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_module'] = array
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
			array('tl_module', 'checkPermission'),
			array('tl_module', 'addCustomLayoutSectionReferences')
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
			'child_record_callback'   => array('tl_module', 'listModule')
		),
		'label' => array
		(
			'group_callback'          => array('tl_module', 'getGroupHeader')
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
				'icon'                => 'copy.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
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
		'__selector__'                => array('type', 'defineRoot', 'protected', 'reg_assignDir', 'reg_activate'),
		'default'                     => '{title_legend},name,type',
		'navigation'                  => '{title_legend},name,headline,type;{nav_legend},levelOffset,showLevel,hardLimit,showProtected,showHidden;{reference_legend:hide},defineRoot;{template_legend:hide},customTpl,navigationTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'customnav'                   => '{title_legend},name,headline,type;{nav_legend},pages,showProtected;{template_legend:hide},customTpl,navigationTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'breadcrumb'                  => '{title_legend},name,headline,type;{nav_legend},showHidden;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'quicknav'                    => '{title_legend},name,headline,type;{label_legend},customLabel;{nav_legend},showLevel,hardLimit,showProtected,showHidden;{reference_legend:hide},rootPage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'quicklink'                   => '{title_legend},name,headline,type;{label_legend},customLabel;{nav_legend},pages,showProtected;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'booknav'                     => '{title_legend},name,headline,type;{nav_legend},showProtected,showHidden;{reference_legend:hide},rootPage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'articlenav'                  => '{title_legend},name,headline,type;{config_legend},loadFirst;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'sitemap'                     => '{title_legend},name,headline,type;{nav_legend},showProtected,showHidden;{reference_legend:hide},rootPage;{template_legend:hide},customTpl,navigationTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'login'                       => '{title_legend},name,headline,type;{config_legend},autologin;{redirect_legend},jumpTo,redirectBack;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'logout'                      => '{title_legend},name,type;{redirect_legend},jumpTo,redirectBack;{protected_legend:hide},protected;{expert_legend:hide},cssID',
		'personalData'                => '{title_legend},name,headline,type;{config_legend},editable;{redirect_legend},jumpTo;{template_legend:hide},memberTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID',
		'registration'                => '{title_legend},name,headline,type;{config_legend},editable,newsletters,disableCaptcha;{account_legend},reg_groups,reg_allowLogin,reg_assignDir;{redirect_legend},jumpTo;{email_legend},reg_activate;{template_legend:hide},memberTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'changePassword'              => '{title_legend},name,headline,type;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID',
		'lostPassword'                => '{title_legend},name,headline,type;{config_legend},reg_skipName,disableCaptcha;{redirect_legend},jumpTo;{email_legend:hide},reg_jumpTo,reg_password;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'closeAccount'                => '{title_legend},name,headline,type;{config_legend},reg_close,reg_deleteDir;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID',
		'form'                        => '{title_legend},name,headline,type;{include_legend},form;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'search'                      => '{title_legend},name,headline,type;{config_legend},queryType,fuzzy,contextLength,minKeywordLength,perPage,searchType;{redirect_legend:hide},jumpTo;{reference_legend:hide},pages;{template_legend:hide},searchTpl,customTpl;{image_legend},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'articlelist'                 => '{title_legend},name,headline,type;{config_legend},skipFirst,inColumn;{reference_legend:hide},defineRoot;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'randomImage'                 => '{title_legend},name,headline,type;{source_legend},multiSRC,imgSize,fullsize,useCaption;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'html'                        => '{title_legend},name,type;{html_legend},html;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests',
		'template'                    => '{title_legend},name,headline,type;{template_legend},data,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'rssReader'                   => '{title_legend},name,headline,type;{config_legend},rss_feed,numberOfItems,perPage,skipFirst,rss_cache;{template_legend:hide},rss_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'two_factor'                  => '{title_legend},name,headline,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID',
		'root_page_dependent_modules' => '{title_legend},name,type;{config_legend},rootPageDependentModules;{protected_legend:hide},protected'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'defineRoot'                  => 'rootPage',
		'protected'                   => 'groups',
		'reg_assignDir'               => 'reg_homeDir',
		'reg_activate'                => 'reg_jumpTo,reg_text'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'search'                  => true,
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
			'exclude'                 => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'headline' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
			'eval'                    => array('maxlength'=>200, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default 'a:2:{s:5:\"value\";s:0:\"\";s:4:\"unit\";s:2:\"h2\";}'"
		),
		'type' => array
		(
			'exclude'                 => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'filter'                  => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_module', 'getModules'),
			'reference'               => &$GLOBALS['TL_LANG']['FMD'],
			'eval'                    => array('helpwizard'=>true, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default 'navigation'"
		),
		'levelOffset' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>5, 'rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'showLevel' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>5, 'rgxp'=>'natural', 'tl_class'=>'w50 clr'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'hardLimit' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'showProtected' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'defineRoot' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'rootPage' => array
		(
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'navigationTpl' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('nav_');
			},
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'customTpl' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'pages' => array
		(
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'isSortable'=>true, 'mandatory'=>true),
			'load_callback' => array
			(
				array('tl_module', 'setPagesFlags')
			),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'showHidden' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'customLabel' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'rgxp'=>'extnd', 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'autologin' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'jumpTo' => array
		(
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'overviewPage' => array
		(
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'redirectBack' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'editable' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkboxWizard',
			'options_callback'        => array('tl_module', 'getEditableMemberProperties'),
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'memberTpl' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('member_');
			},
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'form' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_form.title',
			'options_callback'        => array('tl_module', 'getForms'),
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'queryType' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('and', 'or'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(8) COLLATE ascii_bin NOT NULL default 'and'"
		),
		'fuzzy' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'contextLength' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'natural', 'tl_class'=>'w50', 'placeholder'=>array(48, 360)),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'minKeywordLength' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 4"
		),
		'perPage' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'searchType' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('simple', 'advanced'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(16) COLLATE ascii_bin NOT NULL default 'simple'"
		),
		'searchTpl' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('search_');
			},
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'inColumn' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_module', 'getLayoutSections'),
			'reference'               => &$GLOBALS['TL_LANG']['COLS'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default 'main'"
		),
		'skipFirst' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'loadFirst' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'singleSRC' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'mandatory'=>true, 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),
		'url' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'tl_class'=>'w50'),
			'sql'                     => "text NULL"
		),
		'imgSize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) COLLATE ascii_bin NOT NULL default ''"
		),
		'useCaption' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'fullsize' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'multiSRC' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'orderField'=>'orderSRC', 'files'=>true, 'mandatory'=>true),
			'load_callback' => array
			(
				array('tl_module', 'setMultiSrcFlags')
			),
			'sql'                     => "blob NULL"
		),
		'orderSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['sortOrder'],
			'sql'                     => "blob NULL"
		),
		'html' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('allowHtml'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => "text NULL"
		),
		'rss_cache' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array(0, 5, 15, 30, 60, 300, 900, 1800, 3600, 10800, 21600, 43200, 86400),
			'eval'                    => array('tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['CACHE'],
			'sql'                     => "int(10) unsigned NOT NULL default 3600"
		),
		'rss_feed' => array
		(
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'style'=>'height:60px'),
			'sql'                     => "text NULL"
		),
		'rss_template' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback' => static function ()
			{
				return Controller::getTemplateGroup('rss_');
			},
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'numberOfItems' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['numberOfItems'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 3"
		),
		'disableCaptcha' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'reg_groups' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'reg_allowLogin' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'reg_skipName' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'reg_close' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('close_deactivate', 'close_delete'),
			'eval'                    => array('tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
			'sql'                     => "varchar(32) COLLATE ascii_bin NOT NULL default ''"
		),
		'reg_deleteDir' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'reg_assignDir' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'reg_homeDir' => array
		(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),
		'reg_activate' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'reg_jumpTo' => array
		(
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'reg_text' => array
		(
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:120px', 'decodeEntities'=>true, 'alwaysSave'=>true),
			'load_callback' => array
			(
				array('tl_module', 'getActivationDefault')
			),
			'sql'                     => "text NULL"
		),
		'reg_password' => array
		(
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:120px', 'decodeEntities'=>true, 'alwaysSave'=>true),
			'load_callback'           => array
			(
				array('tl_module', 'getPasswordDefault')
			),
			'sql'                     => "text NULL"
		),
		'data' => array
		(
			'exclude'                 => true,
			'inputType'               => 'keyValueWizard',
			'sql'                     => "text NULL"
		),
		'protected' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'groups' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'guests' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) COLLATE ascii_bin NOT NULL default ''"
		),
		'cssID' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'rootPageDependentModules' => array
		(
			'exclude'                 => true,
			'inputType'               => 'rootPageDependentSelect',
			'eval'                    => array('submitOnChange'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => 'blob NULL'
		),
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_module extends Backend
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

		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULES))
		{
			throw new AccessDeniedException('Not enough permissions to access the front end modules module.');
		}
	}

	/**
	 * Return all front end modules as array
	 *
	 * @return array
	 */
	public function getModules()
	{
		$groups = array();

		foreach ($GLOBALS['FE_MOD'] as $k=>$v)
		{
			foreach (array_keys($v) as $kk)
			{
				$groups[$k][] = $kk;
			}
		}

		return $groups;
	}

	/**
	 * Return all editable fields of table tl_member
	 *
	 * @return array
	 */
	public function getEditableMemberProperties()
	{
		$return = array();

		System::loadLanguageFile('tl_member');
		$this->loadDataContainer('tl_member');

		foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $k=>$v)
		{
			if ($v['eval']['feEditable'] ?? null)
			{
				$return[$k] = $GLOBALS['TL_DCA']['tl_member']['fields'][$k]['label'][0];
			}
		}

		return $return;
	}

	/**
	 * Get all forms and return them as array
	 *
	 * @return array
	 */
	public function getForms()
	{
		if (!$this->User->isAdmin && !is_array($this->User->forms))
		{
			return array();
		}

		$arrForms = array();
		$objForms = $this->Database->execute("SELECT id, title FROM tl_form ORDER BY title");
		$security = System::getContainer()->get('security.helper');

		while ($objForms->next())
		{
			if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FORM, $objForms->id))
			{
				$arrForms[$objForms->id] = $objForms->title;
			}
		}

		return $arrForms;
	}

	/**
	 * Return all layout sections as array
	 *
	 * @return array
	 */
	public function getLayoutSections()
	{
		$arrSections = array('header', 'left', 'right', 'main', 'footer');

		// Check for custom layout sections
		$objLayout = $this->Database->query("SELECT sections FROM tl_layout WHERE sections!=''");

		while ($objLayout->next())
		{
			$arrCustom = StringUtil::deserialize($objLayout->sections);

			// Add the custom layout sections
			if (!empty($arrCustom) && is_array($arrCustom))
			{
				foreach ($arrCustom as $v)
				{
					if (!empty($v['id']))
					{
						$arrSections[] = $v['id'];
					}
				}
			}
		}

		return Backend::convertLayoutSectionIdsToAssociativeArray($arrSections);
	}

	/**
	 * Use the module type as group header if sorted by type (see #8402)
	 *
	 * @param string $group
	 * @param string $mode
	 * @param string $field
	 * @param array  $row
	 *
	 * @return string
	 */
	public function getGroupHeader($group, $mode, $field, $row)
	{
		if ($field == 'type')
		{
			return $row['type'];
		}

		return $group;
	}

	/**
	 * Load the default activation text
	 *
	 * @param mixed $varValue
	 *
	 * @return mixed
	 */
	public function getActivationDefault($varValue)
	{
		if (trim($varValue) === '')
		{
			$varValue = (is_array($GLOBALS['TL_LANG']['tl_module']['emailText'] ?? null) ? $GLOBALS['TL_LANG']['tl_module']['emailText'][1] : ($GLOBALS['TL_LANG']['tl_module']['emailText'] ?? null));
		}

		return $varValue;
	}

	/**
	 * Load the default password text
	 *
	 * @param mixed $varValue
	 *
	 * @return mixed
	 */
	public function getPasswordDefault($varValue)
	{
		if (trim($varValue) === '')
		{
			$varValue = (is_array($GLOBALS['TL_LANG']['tl_module']['passwordText'] ?? null) ? $GLOBALS['TL_LANG']['tl_module']['passwordText'][1] : ($GLOBALS['TL_LANG']['tl_module']['passwordText'] ?? null));
		}

		return $varValue;
	}

	/**
	 * List a front end module
	 *
	 * @param array $row
	 *
	 * @return string
	 */
	public function listModule($row)
	{
		return '<div class="tl_content_left">' . $row['name'] . ' <span style="color:#999;padding-left:3px">[' . ($GLOBALS['TL_LANG']['FMD'][$row['type']][0] ?? $row['type']) . ']</span></div>';
	}

	/**
	 * Dynamically add flags to the "multiSRC" field
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function setMultiSrcFlags($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->type == 'randomImage')
		{
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['isGallery'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = '%contao.image.valid_extensions%';
		}

		return $varValue;
	}

	/**
	 * Dynamically change attributes of the "pages" field
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function setPagesFlags($varValue, DataContainer $dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->type == 'search')
		{
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['mandatory'] = false;
			unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['isSortable']);
		}

		return $varValue;
	}
}
