<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Automator;
use Contao\Backend;
use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Idna;
use Contao\Image;
use Contao\Input;
use Contao\LayoutModel;
use Contao\Message;
use Contao\Messages;
use Contao\Model;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;

$GLOBALS['TL_DCA']['tl_page'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_article'),
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_page', 'checkPermission'),
			array('tl_page', 'addBreadcrumb'),
			array('tl_page', 'setRootType'),
			array('tl_page', 'showFallbackWarning'),
			array('tl_page', 'makeRedirectPageMandatory'),
		),
		'oncut_callback' => array
		(
			array('tl_page', 'scheduleUpdate')
		),
		'ondelete_callback' => array
		(
			array('tl_page', 'scheduleUpdate')
		),
		'onsubmit_callback' => array
		(
			array('tl_page', 'scheduleUpdate'),
		),
		'oninvalidate_cache_tags_callback' => array
		(
			array('tl_page', 'addSitemapCacheInvalidationTag'),
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'alias' => 'index',
				'type,dns' => 'index',
				'pid,published,type,start,stop' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_TREE,
			'rootPaste'               => true,
			'showRootTrails'          => true,
			'icon'                    => 'pagemounts.svg',
			'paste_button_callback'   => array('tl_page', 'pastePage'),
			'panelLayout'             => 'filter;search',
			'defaultSearchField'      => 'pageTitle'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s',
			'label_callback'          => array('tl_page', 'addIcon')
		),
		'global_operations' => array
		(
			'toggleNodes' => array
			(
				'href'                => 'ptg=all',
				'class'               => 'header_toggle',
				'showOnSelect'        => true
			),
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
				'icon'                => 'edit.svg',
				'button_callback'     => array('tl_page', 'editPage')
			),
			'copy' => array
			(
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_page', 'copyPage')
			),
			'copyChilds' => array
			(
				'href'                => 'act=paste&amp;mode=copy&amp;childs=1',
				'icon'                => 'copychilds.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_page', 'copyPageWithSubpages')
			),
			'cut' => array
			(
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_page', 'cutPage')
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_page', 'deletePage')
			),
			'toggle' => array
			(
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
				'button_callback'     => array('tl_page', 'toggleIcon')
			),
			'show',
			'articles' => array
			(
				'href'                => 'do=article',
				'icon'                => 'article.svg'
			)
		)
	),

	// Select
	'select' => array
	(
		'buttons_callback' => array
		(
			array('tl_page', 'addAliasButton')
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('type', 'fallback', 'autoforward', 'protected', 'includeLayout', 'includeCache', 'includeChmod', 'enforceTwoFactor'),
		'default'                     => '{title_legend},title,type',
		'regular'                     => '{title_legend},title,type;{routing_legend},alias,requireItem,routePath,routePriority,routeConflicts;{meta_legend},pageTitle,robots,description,serpPreview;{canonical_legend:hide},canonicalLink,canonicalKeepParams;{protected_legend:hide},protected;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass,sitemap,hide,noSearch;{tabnav_legend:hide},accesskey;{publish_legend},published,start,stop',
		'forward'                     => '{title_legend},title,type;{routing_legend},alias,routePath,routePriority,routeConflicts;{meta_legend},pageTitle,robots;{redirect_legend},jumpTo,redirect;{protected_legend:hide},protected;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass,sitemap,hide;{tabnav_legend:hide},accesskey;{publish_legend},published,start,stop',
		'redirect'                    => '{title_legend},title,type;{routing_legend},alias,routePath,routePriority,routeConflicts;{meta_legend},pageTitle,robots;{redirect_legend},redirect,url,target;{protected_legend:hide},protected;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass,sitemap,hide;{tabnav_legend:hide},accesskey;{publish_legend},published,start,stop',
		'root'                        => '{title_legend},title,type;{routing_legend},alias;{meta_legend},pageTitle;{url_legend},dns,useSSL,urlPrefix,urlSuffix,validAliasCharacters,useFolderUrl;{language_legend},language,fallback,disableLanguageRedirect;{website_legend:hide},maintenanceMode;{global_legend:hide},mailerTransport,enableCanonical,adminEmail,dateFormat,timeFormat,datimFormat,staticFiles,staticPlugins;{protected_legend:hide},protected;{layout_legend},includeLayout;{twoFactor_legend:hide},enforceTwoFactor;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{publish_legend},published,start,stop',
		'rootfallback'                => '{title_legend},title,type;{routing_legend},alias;{meta_legend},pageTitle;{url_legend},dns,useSSL,urlPrefix,urlSuffix,validAliasCharacters,useFolderUrl;{language_legend},language,fallback,disableLanguageRedirect;{website_legend:hide},favicon,robotsTxt,maintenanceMode;{global_legend:hide},mailerTransport,enableCanonical,adminEmail,dateFormat,timeFormat,datimFormat,staticFiles,staticPlugins;{protected_legend:hide},protected;{layout_legend},includeLayout;{twoFactor_legend:hide},enforceTwoFactor;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{publish_legend},published,start,stop',
		'logout'                      => '{title_legend},title,type;{routing_legend},alias,routePath,routePriority,routeConflicts;{forward_legend},jumpTo,redirectBack;{protected_legend:hide},protected;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass,sitemap,hide;{tabnav_legend:hide},accesskey;{publish_legend},published,start,stop',
		'error_401'                   => '{title_legend},title,type;{meta_legend},pageTitle,robots,description;{forward_legend},autoforward;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass;{publish_legend},published,start,stop',
		'error_403'                   => '{title_legend},title,type;{meta_legend},pageTitle,robots,description;{forward_legend},autoforward;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass;{publish_legend},published,start,stop',
		'error_404'                   => '{title_legend},title,type;{meta_legend},pageTitle,robots,description;{forward_legend},autoforward;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass;{publish_legend},published,start,stop',
		'error_503'                   => '{title_legend},title,type;{meta_legend},pageTitle,robots,description;{forward_legend},autoforward;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass;{publish_legend},published,start,stop'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'autoforward'                 => 'jumpTo',
		'protected'                   => 'groups',
		'includeLayout'               => 'layout,subpageLayout',
		'includeCache'                => 'clientCache,cache,alwaysLoadFromCache',
		'includeChmod'                => 'cuser,cgroup,chmod',
		'enforceTwoFactor'            => 'twoFactorJumpTo'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'label'                   => array('ID'),
			'search'                  => true,
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'sorting' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'title' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'type' => array
		(
			'filter'                  => true,
			'inputType'               => 'select',
			'eval'                    => array('helpwizard'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['PTY'],
			'sql'                     => "varchar(64) NOT NULL default 'regular'"
		),
		'alias' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'folderalias', 'doNotCopy'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
		'requireItem' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'routePath' => array
		(
			// input_field_callback from PageRoutingListener
		),
		'routePriority' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "int(10) NOT NULL default 0"
		),
		'routeConflicts' => array
		(
			// input_field_callback from PageRoutingListener
		),
		'pageTitle' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'language' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64, 'nospace'=>true, 'decodeEntities'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''",
			'save_callback'           => array
			(
				static function ($value)
				{
					// Make sure there is at least a basic language
					if (!preg_match('/^[a-z]{2,}/i', $value))
					{
						throw new RuntimeException($GLOBALS['TL_LANG']['ERR']['language']);
					}

					return LocaleUtil::canonicalize($value);
				}
			)
		),
		'robots' => array
		(
			'search'                  => true,
			'inputType'               => 'select',
			'options'                 => array('index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'description' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'serpPreview' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
			'inputType'               => 'serpPreview',
			'eval'                    => array('url_callback'=>array('tl_page', 'getSerpUrl'), 'title_tag_callback'=>array('tl_page', 'getTitleTag'), 'titleFields'=>array('pageTitle', 'title'), 'tl_class'=>'clr'),
			'sql'                     => null
		),
		'redirect' => array
		(
			'inputType'               => 'select',
			'options'                 => array('permanent', 'temporary'),
			'eval'                    => array('tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_page'],
			'sql'                     => "varchar(32) NOT NULL default 'permanent'"
		),
		'jumpTo' => array
		(
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio'), // do not set mandatory (see #5453)
			'save_callback' => array
			(
				array('tl_page', 'checkJumpTo')
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'redirectBack' => array
		(
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['url'],
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'target' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['target'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'dns' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'load_callback' => array
			(
				array('tl_page', 'loadDns')
			),
			'save_callback' => array
			(
				array('tl_page', 'checkDns')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'staticFiles' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'trailingSlash'=>false, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_page', 'checkStaticUrl')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'staticPlugins' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'trailingSlash'=>false, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_page', 'checkStaticUrl')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'fallback' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'save_callback' => array
			(
				array('tl_page', 'checkFallback')
			),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'disableLanguageRedirect' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'favicon' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio', 'extensions'=>'ico,svg'),
			'sql'                     => "binary(16) NULL"
		),
		'robotsTxt' => array
		(
			'inputType'               => 'textarea',
			'eval'                    => array('doNotCopy'=>true, 'decodeEntities' => true),
			'sql'                     => "text NULL"
		),
		'maintenanceMode' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'mailerTransport' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('tl_class'=>'w50', 'includeBlankOption'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'enableCanonical' => array
		(
			'inputType'               => 'checkbox',
			'default'                 => true,
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'canonicalLink' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048, 'dcaPicker'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'canonicalKeepParams' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'adminEmail' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'rgxp'=>'friendly', 'decodeEntities'=>true, 'placeholder'=>Config::get('adminEmail'), 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'dateFormat' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('helpwizard'=>true, 'decodeEntities'=>true, 'placeholder'=>Config::get('dateFormat'), 'tl_class'=>'w50'),
			'explanation'             => 'dateFormat',
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'timeFormat' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'placeholder'=>Config::get('timeFormat'), 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'datimFormat' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'placeholder'=>Config::get('datimFormat'), 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'validAliasCharacters' => array
		(
			'inputType'               => 'select',
			'options_callback' => static function ()
			{
				return System::getContainer()->get('contao.slug.valid_characters')->getOptions();
			},
			'eval'                    => array('includeBlankOption'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'useFolderUrl' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'urlPrefix' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'folderalias', 'doNotCopy'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) BINARY NOT NULL default ''"
		),
		'urlSuffix' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('nospace'=>'true', 'maxlength'=>16, 'tl_class'=>'w50'),
			'sql'                     => "varchar(16) NOT NULL default ''"
		),
		'useSSL' => array
		(
			'inputType'               => 'select',
			'options'                 => array('http://', 'https://'),
			'eval'                    => array('tl_class'=>'w50', 'isAssociative' => true),
			'sql'                     => array('type' => 'boolean', 'default' => true)
		),
		'autoforward' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'protected' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'groups' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'includeLayout' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'layout' => array
		(
			'search'                  => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_layout.name',
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'subpageLayout' => array
		(
			'search'                  => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_layout.name',
			'eval'                    => array('chosen'=>true, 'tl_class'=>'w50', 'includeBlankOption'=>true, 'blankOptionLabel'=>&$GLOBALS['TL_LANG']['tl_page']['layout_inherit']),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'includeCache' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'cache' => array
		(
			'search'                  => true,
			'inputType'               => 'select',
			'options'                 => array(0, 5, 15, 30, 60, 300, 900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2592000, 7776000, 15552000, 31536000),
			'reference'               => &$GLOBALS['TL_LANG']['CACHE'],
			'eval'                    => array('tl_class'=>'w50 clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'alwaysLoadFromCache' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'clientCache' => array
		(
			'search'                  => true,
			'inputType'               => 'select',
			'options'                 => array(0, 5, 15, 30, 60, 300, 900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2592000),
			'reference'               => &$GLOBALS['TL_LANG']['CACHE'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'includeChmod' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'cuser' => array
		(
			'default'                 => (int) Config::get('defaultUser'),
			'search'                  => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.name',
			'eval'                    => array('mandatory'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'cgroup' => array
		(
			'default'                 => (int) Config::get('defaultGroup'),
			'search'                  => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user_group.name',
			'eval'                    => array('mandatory'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'chmod' => array
		(
			'default'                 => Config::get('defaultChmod'),
			'inputType'               => 'chmod',
			'eval'                    => array('tl_class'=>'clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'noSearch' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'cssClass' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'sitemap' => array
		(
			'inputType'               => 'select',
			'options'                 => array('map_default', 'map_always', 'map_never'),
			'eval'                    => array('maxlength'=>32, 'tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_page'],
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'hide' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'accesskey' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'maxlength'=>1, 'tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'published' => array
		(
			'toggle'                  => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
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
		),
		'enforceTwoFactor' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'twoFactorJumpTo' => array
		(
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio', 'mandatory'=>true),
			'save_callback' => array
			(
				array('tl_page', 'checkJumpTo')
			),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		)
	)
);

// Disable the articles link in the modal window
if (Input::get('popup'))
{
	unset($GLOBALS['TL_DCA']['tl_page']['list']['operations']['articles']);
}

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_page extends Backend
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
	 * Check permissions to edit table tl_page
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();
		$session = $objSession->all();

		// Set the default page user and group
		$GLOBALS['TL_DCA']['tl_page']['fields']['cuser']['default'] = (int) Config::get('defaultUser') ?: $this->User->id;
		$GLOBALS['TL_DCA']['tl_page']['fields']['cgroup']['default'] = (int) Config::get('defaultGroup') ?: (int) $this->User->groups[0];

		// Restrict the page tree
		if (empty($this->User->pagemounts) || !is_array($this->User->pagemounts))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->pagemounts;
		}

		$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['rootPaste'] = false;
		$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = $root;
		$security = System::getContainer()->get('security.helper');

		// Set allowed page IDs (edit multiple)
		if (is_array($session['CURRENT']['IDS'] ?? null))
		{
			$edit_all = array();
			$delete_all = array();

			foreach ($session['CURRENT']['IDS'] as $id)
			{
				$objPage = $this->Database->prepare("SELECT id, pid, type, includeChmod, chmod, cuser, cgroup FROM tl_page WHERE id=?")
										  ->limit(1)
										  ->execute($id);

				if ($objPage->numRows < 1 || !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $objPage->type))
				{
					continue;
				}

				$row = $objPage->row();

				if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE, $row))
				{
					$edit_all[] = $id;
				}

				// Mounted pages cannot be deleted
				if ($security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_PAGE, $row) && !$this->User->hasAccess($id, 'pagemounts'))
				{
					$delete_all[] = $id;
				}
			}

			$session['CURRENT']['IDS'] = (Input::get('act') == 'deleteAll') ? $delete_all : $edit_all;
		}

		// Set allowed clipboard IDs
		if (is_array($session['CLIPBOARD']['tl_page']['id'] ?? null))
		{
			$clipboard = array();

			foreach ($session['CLIPBOARD']['tl_page']['id'] as $id)
			{
				$objPage = $this->Database->prepare("SELECT id, pid, type, includeChmod, chmod, cuser, cgroup FROM tl_page WHERE id=?")
										  ->limit(1)
										  ->execute($id);

				if ($objPage->numRows < 1 || !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $objPage->type))
				{
					continue;
				}

				if ($security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, $objPage->row()))
				{
					$clipboard[] = $id;
				}
			}

			$session['CLIPBOARD']['tl_page']['id'] = $clipboard;
		}

		// Overwrite session
		$objSession->replace($session);

		// Check permissions to save and create new
		if (Input::get('act') == 'edit')
		{
			$objPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=(SELECT pid FROM tl_page WHERE id=?)")
									  ->limit(1)
									  ->execute(Input::get('id'));

			if ($objPage->numRows && !$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, $objPage->row()))
			{
				$GLOBALS['TL_DCA']['tl_page']['config']['closed'] = true;
			}
		}

		// Check current action
		if (Input::get('act') && Input::get('act') != 'paste')
		{
			$permission = null;
			$cid = Input::get('id');
			$ids = $cid ? array($cid) : array();

			// Set permission
			switch (Input::get('act'))
			{
				case 'edit':
				case 'toggle':
					$permission = ContaoCorePermissions::USER_CAN_EDIT_PAGE;
					break;

				case 'create':
				case 'copy':
				case 'copyAll':
				case 'cut':
				case 'cutAll':
					$permission = ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY;

					// Check the parent page in "paste into" mode
					if (Input::get('mode') == 2)
					{
						$ids[] = Input::get('pid');
					}
					// Check the parent's parent page in "paste after" mode
					else
					{
						$objPage = $this->Database->prepare("SELECT pid FROM tl_page WHERE id=?")
												  ->limit(1)
												  ->execute(Input::get('pid'));

						$ids[] = $objPage->pid;
					}
					break;

				case 'delete':
					$permission = ContaoCorePermissions::USER_CAN_DELETE_PAGE;
					break;
			}

			// Check user permissions
			$pagemounts = array();

			// Get all allowed pages for the current user
			foreach ($this->User->pagemounts as $root)
			{
				if (Input::get('act') != 'delete')
				{
					$pagemounts[] = array($root);
				}

				$pagemounts[] = $this->Database->getChildRecords($root, 'tl_page');
			}

			if (!empty($pagemounts))
			{
				$pagemounts = array_merge(...$pagemounts);
			}

			$pagemounts = array_unique($pagemounts);

			// Do not allow pasting after pages on the root level (pagemounts)
			if (Input::get('mode') == 1 && (Input::get('act') == 'cut' || Input::get('act') == 'cutAll') && in_array(Input::get('pid'), $this->eliminateNestedPages($this->User->pagemounts)))
			{
				throw new AccessDeniedException('Not enough permissions to paste page ID ' . Input::get('id') . ' after mounted page ID ' . Input::get('pid') . ' (root level).');
			}

			$error = false;

			// Check each page
			foreach ($ids as $i=>$id)
			{
				if (!in_array($id, $pagemounts))
				{
					System::getContainer()->get('monolog.logger.contao.error')->error('Page ID ' . $id . ' was not mounted');

					$error = true;
					break;
				}

				// Get the page object
				$objPage = PageModel::findById($id);

				if ($objPage === null)
				{
					continue;
				}

				// Check whether the current user is allowed to access the current page
				if (Input::get('act') != 'show' && ($permission === null || !$security->isGranted($permission, $objPage->row())))
				{
					$error = true;
					break;
				}

				// Check the type of the first page (not the following parent pages)
				// In "edit multiple" mode, $ids contains only the parent ID, therefore check $id != $_GET['pid'] (see #5620)
				if ($i == 0 && $id != Input::get('pid') && Input::get('act') != 'create' && !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $objPage->type))
				{
					System::getContainer()->get('monolog.logger.contao.error')->error('Not enough permissions to  ' . Input::get('act') . ' ' . $objPage->type . ' pages');

					$error = true;
					break;
				}
			}

			// Redirect if there is an error
			if ($error)
			{
				throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' page ID ' . $cid . ' or paste after/into page ID ' . Input::get('pid') . '.');
			}
		}
	}

	/**
	 * Add the breadcrumb menu
	 */
	public function addBreadcrumb()
	{
		Backend::addPagesBreadcrumb();
	}

	/**
	 * Make new top-level pages root pages
	 *
	 * @param DataContainer $dc
	 */
	public function setRootType(DataContainer $dc)
	{
		if (Input::get('act') != 'create')
		{
			return;
		}

		// Insert into
		if (Input::get('pid') == 0)
		{
			$GLOBALS['TL_DCA']['tl_page']['fields']['type']['default'] = 'root';
		}
		elseif (Input::get('mode') == 1)
		{
			$objPage = $this->Database->prepare("SELECT * FROM " . $dc->table . " WHERE id=?")
									  ->limit(1)
									  ->execute(Input::get('pid'));

			if ($objPage->pid == 0)
			{
				$GLOBALS['TL_DCA']['tl_page']['fields']['type']['default'] = 'root';
			}
		}
	}

	/**
	 * Return the SERP URL
	 *
	 * @param PageModel $page
	 *
	 * @return string
	 */
	public function getSerpUrl(PageModel $page)
	{
		return $page->getAbsoluteUrl();
	}

	/**
	 * Return the title tag from the associated page layout
	 *
	 * @param PageModel $page
	 *
	 * @return string
	 */
	public function getTitleTag(PageModel $page)
	{
		$page->loadDetails();

		/** @var LayoutModel $layout */
		if (!$layout = $page->getRelated('layout'))
		{
			return '';
		}

		$origObjPage = $GLOBALS['objPage'] ?? null;

		// Override the global page object, so we can replace the insert tags
		$GLOBALS['objPage'] = $page;

		$title = implode(
			'%s',
			array_map(
				static function ($strVal)
				{
					return str_replace('%', '%%', System::getContainer()->get('contao.insert_tag.parser')->replaceInline($strVal));
				},
				explode('{{page::pageTitle}}', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}', 2)
			)
		);

		$GLOBALS['objPage'] = $origObjPage;

		return $title;
	}

	/**
	 * Show a warning if there is no language fallback page
	 */
	public function showFallbackWarning()
	{
		if (Input::get('act'))
		{
			return;
		}

		$messages = new Messages();
		Message::addRaw($messages->languageFallback());
	}

	/**
	 * Make the redirect page mandatory if the page is a logout page
	 *
	 * @param DataContainer $dc
	 *
	 * @throws Exception
	 */
	public function makeRedirectPageMandatory(DataContainer $dc)
	{
		$objPage = $this->Database->prepare("SELECT * FROM " . $dc->table . " WHERE id=?")
								  ->limit(1)
								  ->execute($dc->id);

		if ($objPage->numRows && $objPage->type == 'logout')
		{
			$GLOBALS['TL_DCA']['tl_page']['fields']['jumpTo']['eval']['mandatory'] = true;
		}
	}

	/**
	 * Schedule a sitemap update
	 *
	 * This method is triggered when a single page or multiple pages are
	 * modified (edit/editAll), moved (cut/cutAll) or deleted
	 * (delete/deleteAll). Since duplicated pages are unpublished by default,
	 * it is not necessary to schedule updates on copyAll as well.
	 *
	 * @param DataContainer $dc
	 */
	public function scheduleUpdate(DataContainer $dc)
	{
		// Return if there is no ID
		if (!$dc->activeRecord || !$dc->activeRecord->id || Input::get('act') == 'copy')
		{
			return;
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Store the ID in the session
		$session = $objSession->get('sitemap_updater');
		$session[] = PageModel::findWithDetails($dc->activeRecord->id)->rootId;
		$objSession->set('sitemap_updater', array_unique($session));
	}

	/**
	 * Check the sitemap alias
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function checkFeedAlias($varValue, DataContainer $dc)
	{
		// No change or empty value
		if (!$varValue || $varValue == $dc->value)
		{
			return $varValue;
		}

		$varValue = StringUtil::standardize($varValue); // see #5096

		$this->import(Automator::class, 'Automator');
		$arrFeeds = $this->Automator->purgeXmlFiles(true);

		// Alias exists
		if (in_array($varValue, $arrFeeds))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
		}

		return $varValue;
	}

	/**
	 * Prevent circular references
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function checkJumpTo($varValue, DataContainer $dc)
	{
		if ($varValue == $dc->id)
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['circularReference']);
		}

		return $varValue;
	}

	/**
	 * Load the DNS settings
	 *
	 * @param string $varValue
	 *
	 * @return string
	 */
	public function loadDns($varValue)
	{
		return Idna::decode($varValue);
	}

	/**
	 * Check the DNS settings
	 *
	 * @param string $varValue
	 *
	 * @return string
	 */
	public function checkDns($varValue)
	{
		if (!$varValue)
		{
			return '';
		}

		// The first part will match IPv6 addresses in square brackets. The
		// second part will match domain names and IPv4 addresses.
		preg_match('#^(?:[a-z]+://)?(\[[0-9a-f:]+]|[\pN\pL._-]*)#ui', $varValue, $matches);

		return Idna::encode($matches[1]);
	}

	/**
	 * Make sure there is only one fallback per domain (thanks to Andreas Schempp)
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function checkFallback($varValue, DataContainer $dc)
	{
		if (!$varValue)
		{
			return '';
		}

		$objPage = $this->Database->prepare("SELECT id FROM tl_page WHERE type='root' AND fallback=1 AND dns=? AND id!=?")
								  ->execute($dc->activeRecord->dns, $dc->activeRecord->id);

		if ($objPage->numRows)
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['multipleFallback']);
		}

		return $varValue;
	}

	/**
	 * Check a static URL
	 *
	 * @param mixed $varValue
	 *
	 * @return mixed
	 */
	public function checkStaticUrl($varValue)
	{
		if ($varValue)
		{
			$varValue = preg_replace('@https?://@', '', $varValue);
		}

		return $varValue;
	}

	/**
	 * Add an image to each page in the tree
	 *
	 * @param array         $row
	 * @param string        $label
	 * @param DataContainer $dc
	 * @param string        $imageAttribute
	 * @param boolean       $blnReturnImage
	 * @param boolean       $blnProtected
	 * @param boolean       $isVisibleRootTrailPage
	 *
	 * @return string
	 */
	public function addIcon($row, $label, DataContainer $dc=null, $imageAttribute='', $blnReturnImage=false, $blnProtected=false, $isVisibleRootTrailPage=false)
	{
		return Backend::addPageIcon($row, $label, $dc, $imageAttribute, $blnReturnImage, $blnProtected, $isVisibleRootTrailPage);
	}

	/**
	 * Return the edit page button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function editPage($row, $href, $label, $title, $icon, $attributes)
	{
		$security = System::getContainer()->get('security.helper');

		return ($security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $row['type']) && $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE, $row)) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the copy page button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 * @param string $table
	 *
	 * @return string
	 */
	public function copyPage($row, $href, $label, $title, $icon, $attributes, $table)
	{
		if ($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? null)
		{
			return '';
		}

		$security = System::getContainer()->get('security.helper');

		return ($security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $row['type']) && $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, $row)) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the copy page with subpages button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 * @param string $table
	 *
	 * @return string
	 */
	public function copyPageWithSubpages($row, $href, $label, $title, $icon, $attributes, $table)
	{
		if ($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? null)
		{
			return '';
		}

		$security = System::getContainer()->get('security.helper');
		$objSubpages = PageModel::findByPid($row['id']);

		return ($objSubpages !== null && $objSubpages->count() > 0 && $security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $row['type']) && $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, $row)) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the cut page button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function cutPage($row, $href, $label, $title, $icon, $attributes)
	{
		$security = System::getContainer()->get('security.helper');

		return ($security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $row['type']) && $security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, $row)) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the paste page button
	 *
	 * @param DataContainer $dc
	 * @param array         $row
	 * @param string        $table
	 * @param boolean       $cr
	 * @param array         $arrClipboard
	 *
	 * @return string
	 */
	public function pastePage(DataContainer $dc, $row, $table, $cr, $arrClipboard=null)
	{
		$security = System::getContainer()->get('security.helper');

		$disablePA = false;
		$disablePI = false;

		// Disable all buttons if there is a circular reference
		if ($arrClipboard !== false && (($arrClipboard['mode'] == 'cut' && ($cr == 1 || $arrClipboard['id'] == $row['id'])) || ($arrClipboard['mode'] == 'cutAll' && ($cr == 1 || in_array($row['id'], $arrClipboard['id'])))))
		{
			$disablePA = true;
			$disablePI = true;
		}

		// Prevent adding non-root pages on top-level
		if (empty($row['pid']) && Input::get('mode') != 'create')
		{
			$objPage = $this->Database->prepare("SELECT * FROM " . $table . " WHERE id=?")
									  ->limit(1)
									  ->execute(Input::get('id'));

			if ($objPage->type != 'root')
			{
				$disablePA = true;

				if ($row['id'] == 0)
				{
					$disablePI = true;
				}
			}
		}

		// Check permissions if the user is not an administrator
		if (!$this->User->isAdmin)
		{
			// Disable "paste into" button if there is no permission 2 (move) or 1 (create) for the current page
			if (!$disablePI)
			{
				if (!$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, $row) || (Input::get('mode') == 'create' && !$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE, $row)))
				{
					$disablePI = true;
				}
			}

			// Disable "paste after" button if there is no permission 2 (move) or 1 (create) for the parent page
			if (!$disablePA)
			{
				/** @var PageModel $objModel */
				$objModel = Model::getClassFromTable($table);

				if (($objPage = $objModel::findById($row['pid'])) !== null && (!$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, $objPage->row()) || (Input::get('mode') == 'create' && !$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE, $objPage->row()))))
				{
					$disablePA = true;
				}
			}

			// Disable "paste after" button if the parent page is a root page and the user is not an administrator
			if (!$disablePA && ($row['pid'] < 1 || in_array($row['id'], $dc->rootIds)))
			{
				$disablePA = true;
			}
		}

		$return = '';

		// Return the buttons
		$imagePasteAfter = Image::getHtml('pasteafter.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id']));
		$imagePasteInto = Image::getHtml('pasteinto.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id']));

		if ($row['id'] > 0)
		{
			$return = $disablePA ? Image::getHtml('pasteafter_.svg') . ' ' : '<a href="' . $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=1&amp;pid=' . $row['id'] . (!is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')) . '" title="' . StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id'])) . '" onclick="Backend.getScrollOffset()">' . $imagePasteAfter . '</a> ';
		}

		return $return . ($disablePI ? Image::getHtml('pasteinto_.svg') . ' ' : '<a href="' . $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=2&amp;pid=' . $row['id'] . (!is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')) . '" title="' . StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][$row['id'] > 0 ? 1 : 0], $row['id'])) . '" onclick="Backend.getScrollOffset()">' . $imagePasteInto . '</a> ');
	}

	/**
	 * Return the delete page button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function deletePage($row, $href, $label, $title, $icon, $attributes)
	{
		$root = func_get_arg(7);
		$security = System::getContainer()->get('security.helper');

		return ($security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $row['type']) && $security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_PAGE, $row) && ($this->User->isAdmin || !in_array($row['id'], $root))) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Automatically generate the folder URL aliases
	 *
	 * @param array         $arrButtons
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addAliasButton($arrButtons, DataContainer $dc)
	{
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_page::alias'))
		{
			return $arrButtons;
		}

		// Generate the aliases
		if (Input::post('alias') !== null && Input::post('FORM_SUBMIT') == 'tl_select')
		{
			$objSession = System::getContainer()->get('request_stack')->getSession();
			$session = $objSession->all();
			$ids = $session['CURRENT']['IDS'] ?? array();

			foreach ($ids as $id)
			{
				$objPage = PageModel::findWithDetails($id);

				if ($objPage === null)
				{
					continue;
				}

				$dc->id = $id;
				$dc->activeRecord = $objPage;

				$strAlias = '';

				// Generate new alias through save callbacks
				if (is_array($GLOBALS['TL_DCA'][$dc->table]['fields']['alias']['save_callback'] ?? null))
				{
					foreach ($GLOBALS['TL_DCA'][$dc->table]['fields']['alias']['save_callback'] as $callback)
					{
						if (is_array($callback))
						{
							$this->import($callback[0]);
							$strAlias = $this->{$callback[0]}->{$callback[1]}($strAlias, $dc);
						}
						elseif (is_callable($callback))
						{
							$strAlias = $callback($strAlias, $dc);
						}
					}
				}

				// The alias has not changed
				if ($strAlias == $objPage->alias)
				{
					continue;
				}

				// Initialize the version manager
				$objVersions = new Versions('tl_page', $id);
				$objVersions->initialize();

				// Store the new alias
				$this->Database->prepare("UPDATE tl_page SET alias=? WHERE id=?")
							   ->execute($strAlias, $id);

				// Create a new version
				$objVersions->create();
			}

			$this->redirect($this->getReferer());
		}

		// Add the button
		$arrButtons['alias'] = '<button type="submit" name="alias" id="alias" class="tl_submit" accesskey="a">' . $GLOBALS['TL_LANG']['MSC']['aliasSelected'] . '</button> ';

		return $arrButtons;
	}

	/**
	 * Return the "toggle visibility" button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
		$security = System::getContainer()->get('security.helper');

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_page::published'))
		{
			return '';
		}

		$href .= '&amp;id=' . $row['id'];

		if (!$row['published'])
		{
			$icon = 'invisible.svg';
		}

		if (!$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE, $row['type']) || ($objPage = PageModel::findById($row['id'])) === null || !$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_PAGE, $objPage->row()))
		{
			return Image::getHtml($icon) . ' ';
		}

		return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '" onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,true)">' . Image::getHtml($icon, $label, 'data-icon="' . Image::getUrl('visible.svg') . '" data-icon-disabled="' . Image::getUrl('invisible.svg') . '" data-state="' . ($row['published'] ? 1 : 0) . '"') . '</a> ';
	}

	/**
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addSitemapCacheInvalidationTag($dc, array $tags)
	{
		$pageModel = PageModel::findWithDetails($dc->id);

		if ($pageModel === null)
		{
			return $tags;
		}

		return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
	}
}
