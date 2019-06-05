<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_module'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
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
			'mode'                    => 4,
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
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_module']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_module']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_module']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_module']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_module']['show'],
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
		'navigation'                  => '{title_legend},name,headline,type;{nav_legend},levelOffset,showLevel,hardLimit,showProtected,showHidden;{reference_legend:hide},defineRoot;{template_legend:hide},navigationTpl,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'customnav'                   => '{title_legend},name,headline,type;{nav_legend},pages,showProtected;{template_legend:hide},navigationTpl,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'breadcrumb'                  => '{title_legend},name,headline,type;{nav_legend},showHidden;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'quicknav'                    => '{title_legend},name,headline,type;{nav_legend},customLabel,showLevel,hardLimit,showProtected,showHidden;{reference_legend:hide},rootPage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'quicklink'                   => '{title_legend},name,headline,type;{nav_legend},pages,customLabel;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'booknav'                     => '{title_legend},name,headline,type;{nav_legend},rootPage,showProtected,showHidden;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'articlenav'                  => '{title_legend},name,headline,type;{config_legend},loadFirst;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'sitemap'                     => '{title_legend},name,headline,type;{nav_legend},showProtected,showHidden;{reference_legend:hide},rootPage;{template_legend:hide},navigationTpl,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'login'                       => '{title_legend},name,headline,type;{config_legend},autologin;{redirect_legend},jumpTo,redirectBack;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'logout'                      => '{title_legend},name,type;{redirect_legend},jumpTo,redirectBack;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'personalData'                => '{title_legend},name,headline,type;{config_legend},editable;{redirect_legend},jumpTo;{template_legend:hide},memberTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'registration'                => '{title_legend},name,headline,type;{config_legend},editable,newsletters,disableCaptcha;{account_legend},reg_groups,reg_allowLogin,reg_assignDir;{redirect_legend},jumpTo;{email_legend},reg_activate;{template_legend:hide},memberTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'changePassword'              => '{title_legend},name,headline,type;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'lostPassword'                => '{title_legend},name,headline,type;{config_legend},reg_skipName,disableCaptcha;{redirect_legend},jumpTo;{email_legend:hide},reg_jumpTo,reg_password;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'closeAccount'                => '{title_legend},name,headline,type;{config_legend},reg_close;{redirect_legend},jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'form'                        => '{title_legend},name,headline,type;{include_legend},form;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'search'                      => '{title_legend},name,headline,type;{config_legend},queryType,fuzzy,contextLength,totalLength,perPage,searchType;{redirect_legend:hide},jumpTo;{reference_legend:hide},pages;{template_legend:hide},searchTpl,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'articlelist'                 => '{title_legend},name,headline,type;{config_legend},skipFirst,inColumn;{reference_legend:hide},defineRoot;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'randomImage'                 => '{title_legend},name,headline,type;{source_legend},multiSRC,imgSize,fullsize,useCaption;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID',
		'html'                        => '{title_legend},name,type;{html_legend},html;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests',
		'rssReader'                   => '{title_legend},name,headline,type;{config_legend},rss_feed,numberOfItems,perPage,skipFirst,rss_cache;{template_legend:hide},rss_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID'
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['name'],
			'exclude'                 => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'headline' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['headline'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'inputUnit',
			'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
			'eval'                    => array('maxlength'=>200, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default 'a:2:{s:5:\"value\";s:0:\"\";s:4:\"unit\";s:2:\"h2\";}'"
		),
		'type' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['type'],
			'exclude'                 => true,
			'sorting'                 => true,
			'flag'                    => 11,
			'filter'                  => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_module', 'getModules'),
			'reference'               => &$GLOBALS['TL_LANG']['FMD'],
			'eval'                    => array('helpwizard'=>true, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default 'navigation'"
		),
		'levelOffset' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['levelOffset'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>5, 'rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'showLevel' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['showLevel'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>5, 'rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'hardLimit' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['hardLimit'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'showProtected' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['showProtected'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'defineRoot' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['defineRoot'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'rootPage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['rootPage'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'navigationTpl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['navigationTpl'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_module', 'getNavigationTemplates'),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'customTpl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['customTpl'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_module', 'getModuleTemplates'),
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'pages' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['pages'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox', 'orderField'=>'orderPages', 'mandatory'=>true),
			'load_callback' => array
			(
				array('tl_module', 'setPagesFlags')
			),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'orderPages' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['sortOrder'],
			'sql'                     => "blob NULL"
		),
		'showHidden' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['showHidden'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'customLabel' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['customLabel'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'rgxp'=>'extnd', 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'autologin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['autologin'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'jumpTo' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['jumpTo'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'redirectBack' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['redirectBack'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'editable' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['editable'],
			'exclude'                 => true,
			'inputType'               => 'checkboxWizard',
			'options_callback'        => array('tl_module', 'getEditableMemberProperties'),
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'memberTpl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['memberTpl'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_module', 'getMemberTemplates'),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'form' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['form'],
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['queryType'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('and', 'or'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default 'and'"
		),
		'fuzzy' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['fuzzy'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'contextLength' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['contextLength'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 48"
		),
		'totalLength' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['totalLength'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 1000"
		),
		'perPage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['perPage'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'searchType' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['searchType'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('simple', 'advanced'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default 'simple'"
		),
		'searchTpl' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['searchTpl'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_module', 'getSearchTemplates'),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'inColumn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['inColumn'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_module', 'getLayoutSections'),
			'reference'               => &$GLOBALS['TL_LANG']['COLS'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default 'main'"
		),
		'skipFirst' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['skipFirst'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'loadFirst' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['loadFirst'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'mandatory'=>true, 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['url'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'imgSize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['imgSize'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'options_callback' => function ()
			{
				return Contao\System::getContainer()->get('contao.image.image_sizes')->getOptionsForUser(Contao\BackendUser::getInstance());
			},
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'useCaption' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['useCaption'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'fullsize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['fullsize'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'multiSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['multiSRC'],
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['html'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('allowHtml'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'helpwizard'=>true),
			'explanation'             => 'insertTags',
			'sql'                     => "text NULL"
		),
		'rss_cache' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['rss_cache'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array(0, 5, 15, 30, 60, 300, 900, 1800, 3600, 10800, 21600, 43200, 86400),
			'eval'                    => array('tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['CACHE'],
			'sql'                     => "int(10) unsigned NOT NULL default 3600"
		),
		'rss_feed' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['rss_feed'],
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'style'=>'height:60px'),
			'sql'                     => "text NULL"
		),
		'rss_template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['rss_template'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_module', 'getRssTemplates'),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default 'rss_default'"
		),
		'numberOfItems' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['numberOfItems'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 3"
		),
		'disableCaptcha' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['disableCaptcha'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'reg_groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'reg_allowLogin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_allowLogin'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'reg_skipName' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_skipName'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'reg_close' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_close'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('close_deactivate', 'close_delete'),
			'eval'                    => array('tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'reg_assignDir' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_assignDir'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'reg_homeDir' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_homeDir'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "binary(16) NULL"
		),
		'reg_activate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_activate'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'reg_jumpTo' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_jumpTo'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'reg_text' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_text'],
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['reg_password'],
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:120px', 'decodeEntities'=>true, 'alwaysSave'=>true),
			'load_callback'           => array
			(
				array('tl_module', 'getPasswordDefault')
			),
			'sql'                     => "text NULL"
		),
		'protected' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['protected'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'guests' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['guests'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'cssID' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_module']['cssID'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_module extends Contao\Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('Contao\BackendUser', 'User');
	}

	/**
	 * Check permissions to edit the table
	 *
	 * @throws Contao\CoreBundle\Exception\AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		if (!$this->User->hasAccess('modules', 'themes'))
		{
			throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access the front end modules module.');
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

		Contao\System::loadLanguageFile('tl_member');
		$this->loadDataContainer('tl_member');

		foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $k=>$v)
		{
			if ($v['eval']['feEditable'])
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
		if (!$this->User->isAdmin && !\is_array($this->User->forms))
		{
			return array();
		}

		$arrForms = array();
		$objForms = $this->Database->execute("SELECT id, title FROM tl_form ORDER BY title");

		while ($objForms->next())
		{
			if ($this->User->hasAccess($objForms->id, 'forms'))
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
			$arrCustom = Contao\StringUtil::deserialize($objLayout->sections);

			// Add the custom layout sections
			if (!empty($arrCustom) && \is_array($arrCustom))
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

		return Contao\Backend::convertLayoutSectionIdsToAssociativeArray($arrSections);
	}

	/**
	 * Return all navigation templates as array
	 *
	 * @return array
	 */
	public function getNavigationTemplates()
	{
		return $this->getTemplateGroup('nav_');
	}

	/**
	 * Return all module templates as array
	 *
	 * @param Contao\DataContainer $dc
	 *
	 * @return array
	 */
	public function getModuleTemplates(Contao\DataContainer $dc)
	{
		return $this->getTemplateGroup('mod_' . $dc->activeRecord->type);
	}

	/**
	 * Return all member templates as array
	 *
	 * @return array
	 */
	public function getMemberTemplates()
	{
		return $this->getTemplateGroup('member_');
	}

	/**
	 * Return all search templates as array
	 *
	 * @return array
	 */
	public function getSearchTemplates()
	{
		return $this->getTemplateGroup('search_');
	}

	/**
	 * Return all navigation templates as array
	 *
	 * @return array
	 */
	public function getRssTemplates()
	{
		return $this->getTemplateGroup('rss_');
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
		if (!trim($varValue))
		{
			$varValue = (\is_array($GLOBALS['TL_LANG']['tl_module']['emailText']) ? $GLOBALS['TL_LANG']['tl_module']['emailText'][1] : $GLOBALS['TL_LANG']['tl_module']['emailText']);
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
		if (!trim($varValue))
		{
			$varValue = (\is_array($GLOBALS['TL_LANG']['tl_module']['passwordText']) ? $GLOBALS['TL_LANG']['tl_module']['passwordText'][1] : $GLOBALS['TL_LANG']['tl_module']['passwordText']);
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
		return '<div class="tl_content_left">'. $row['name'] .' <span style="color:#999;padding-left:3px">['. ($GLOBALS['TL_LANG']['FMD'][$row['type']][0] ?? $row['type']) .']</span></div>';
	}

	/**
	 * Dynamically add flags to the "multiSRC" field
	 *
	 * @param mixed                $varValue
	 * @param Contao\DataContainer $dc
	 *
	 * @return mixed
	 */
	public function setMultiSrcFlags($varValue, Contao\DataContainer $dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->type == 'randomImage')
		{
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['isGallery'] = true;
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['extensions'] = Contao\Config::get('validImageTypes');
		}

		return $varValue;
	}

	/**
	 * Dynamically change attributes of the "pages" field
	 *
	 * @param mixed                $varValue
	 * @param Contao\DataContainer $dc
	 *
	 * @return mixed
	 */
	public function setPagesFlags($varValue, Contao\DataContainer $dc)
	{
		if ($dc->activeRecord && $dc->activeRecord->type == 'search')
		{
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['mandatory'] = false;
			unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['orderField']);
		}

		return $varValue;
	}
}
