<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Back end modules
 */
$GLOBALS['BE_MOD'] = array
(
	// Content modules
	'content' => array
	(
		'article' => array
		(
			'tables'      => array('tl_article', 'tl_content'),
			'table'       => array('TableWizard', 'importTable'),
			'list'        => array('ListWizard', 'importList')
		),
		'form' => array
		(
			'tables'      => array('tl_form', 'tl_form_field')
		)
	),

	// Design modules
	'design' => array
	(
		'themes' => array
		(
			'tables'      => array('tl_theme', 'tl_module', 'tl_style_sheet', 'tl_style', 'tl_layout', 'tl_image_size', 'tl_image_size_item'),
			'importTheme' => array('Theme', 'importTheme'),
			'exportTheme' => array('Theme', 'exportTheme'),
			'import'      => array('StyleSheets', 'importStyleSheet'),
			'export'      => array('StyleSheets', 'exportStyleSheet')
		),
		'page' => array
		(
			'tables'      => array('tl_page')
		),
		'tpl_editor' => array
		(
			'tables'      => array('tl_templates'),
			'new_tpl'     => array('tl_templates', 'addNewTemplate'),
			'compare'     => array('tl_templates', 'compareTemplate'),
		)
	),

	// Account modules
	'accounts' => array
	(
		'member' => array
		(
			'tables'      => array('tl_member')
		),
		'mgroup' => array
		(
			'tables'      => array('tl_member_group')
		),
		'user' => array
		(
			'tables'      => array('tl_user')
		),
		'group' => array
		(
			'tables'      => array('tl_user_group')
		)
	),

	// System modules
	'system' => array
	(
		'files' => array
		(
			'tables'      => array('tl_files')
		),
		'log' => array
		(
			'tables'      => array('tl_log')
		),
		'settings' => array
		(
			'tables'      => array('tl_settings')
		),
		'maintenance' => array
		(
			'callback'    => 'ModuleMaintenance'
		),
		'undo' => array
		(
			'tables'      => array('tl_undo')
		)
	)
);


/**
 * Front end modules
 */
$GLOBALS['FE_MOD'] = array
(
	'navigationMenu' => array
	(
		'navigation'     => 'ModuleNavigation',
		'customnav'      => 'ModuleCustomnav',
		'breadcrumb'     => 'ModuleBreadcrumb',
		'quicknav'       => 'ModuleQuicknav',
		'quicklink'      => 'ModuleQuicklink',
		'booknav'        => 'ModuleBooknav',
		'articlenav'     => 'ModuleArticlenav',
		'sitemap'        => 'ModuleSitemap'
	),
	'user' => array
	(
		'login'          => 'ModuleLogin',
		'logout'         => 'ModuleLogout',
		'personalData'   => 'ModulePersonalData',
		'registration'   => 'ModuleRegistration',
		'changePassword' => 'ModuleChangePassword',
		'lostPassword'   => 'ModulePassword',
		'closeAccount'   => 'ModuleCloseAccount'
	),
	'application' => array
	(
		'form'           => 'Form',
		'search'         => 'ModuleSearch'
	),
	'miscellaneous' => array
	(
		'flash'          => 'ModuleFlash',
		'articlelist'    => 'ModuleArticleList',
		'randomImage'    => 'ModuleRandomImage',
		'html'           => 'ModuleHtml',
		'rssReader'      => 'ModuleRssReader'
	)
);


/**
 * Content elements
 */
$GLOBALS['TL_CTE'] = array
(
	'texts' => array
	(
		'headline'        => 'ContentHeadline',
		'text'            => 'ContentText',
		'html'            => 'ContentHtml',
		'list'            => 'ContentList',
		'table'           => 'ContentTable',
		'code'            => 'ContentCode',
		'markdown'        => 'ContentMarkdown'
	),
	'accordion' => array
	(
		'accordionSingle' => 'ContentAccordion',
		'accordionStart'  => 'ContentAccordionStart',
		'accordionStop'   => 'ContentAccordionStop'
	),
	'slider' => array
	(
		'sliderStart'     => 'ContentSliderStart',
		'sliderStop'      => 'ContentSliderStop'
	),
	'links' => array
	(
		'hyperlink'       => 'ContentHyperlink',
		'toplink'         => 'ContentToplink'
	),
	'media' => array
	(
		'image'           => 'ContentImage',
		'gallery'         => 'ContentGallery',
		'player'          => 'ContentMedia',
		'youtube'         => 'ContentYouTube',
		'vimeo'           => 'ContentVimeo'
	),
	'files' => array
	(
		'download'        => 'ContentDownload',
		'downloads'       => 'ContentDownloads'
	),
	'includes' => array
	(
		'article'         => 'ContentArticle',
		'alias'           => 'ContentAlias',
		'form'            => 'Form',
		'module'          => 'ContentModule',
		'teaser'          => 'ContentTeaser'
	)
);


/**
 * Back end form fields
 */
$GLOBALS['BE_FFL'] = array
(
	'text'           => 'TextField',
	'password'       => 'Password',
	'textStore'      => 'TextStore',
	'textarea'       => 'TextArea',
	'select'         => 'SelectMenu',
	'checkbox'       => 'CheckBox',
	'checkboxWizard' => 'CheckBoxWizard',
	'radio'          => 'RadioButton',
	'radioTable'     => 'RadioTable',
	'inputUnit'      => 'InputUnit',
	'trbl'           => 'TrblField',
	'chmod'          => 'ChmodTable',
	'pageTree'       => 'PageTree',
	'pageSelector'   => 'PageSelector',
	'fileTree'       => 'FileTree',
	'fileSelector'   => 'FileSelector',
	'fileUpload'     => 'Upload',
	'tableWizard'    => 'TableWizard',
	'listWizard'     => 'ListWizard',
	'optionWizard'   => 'OptionWizard',
	'moduleWizard'   => 'ModuleWizard',
	'keyValueWizard' => 'KeyValueWizard',
	'imageSize'      => 'ImageSize',
	'timePeriod'     => 'TimePeriod',
	'metaWizard'     => 'MetaWizard',
	'sectionWizard'  => 'SectionWizard'
);


/**
 * Front end form fields
 */
$GLOBALS['TL_FFL'] = array
(
	'explanation' => 'FormExplanation',
	'html'        => 'FormHtml',
	'fieldset'    => 'FormFieldset',
	'text'        => 'FormTextField',
	'password'    => 'FormPassword',
	'textarea'    => 'FormTextArea',
	'select'      => 'FormSelectMenu',
	'radio'       => 'FormRadioButton',
	'checkbox'    => 'FormCheckBox',
	'upload'      => 'FormFileUpload',
	'hidden'      => 'FormHidden',
	'captcha'     => 'FormCaptcha',
	'submit'      => 'FormSubmit'
);


/**
 * Page types
 */
$GLOBALS['TL_PTY'] = array
(
	'regular'   => 'PageRegular',
	'forward'   => 'PageForward',
	'redirect'  => 'PageRedirect',
	'root'      => 'PageRoot',
	'logout'    => 'PageLogout',
	'error_403' => 'PageError403',
	'error_404' => 'PageError404'
);


/**
 * Maintenance
 */
$GLOBALS['TL_MAINTENANCE'] = array
(
	'Maintenance',
	'RebuildIndex',
	'PurgeData'
);


/**
 * Purge jobs
 */
$GLOBALS['TL_PURGE'] = array
(
	'tables' => array
	(
		'index' => array
		(
			'callback' => array('Automator', 'purgeSearchTables'),
			'affected' => array('tl_search', 'tl_search_index')
		),
		'undo' => array
		(
			'callback' => array('Automator', 'purgeUndoTable'),
			'affected' => array('tl_undo')
		),
		'versions' => array
		(
			'callback' => array('Automator', 'purgeVersionTable'),
			'affected' => array('tl_version')
		),
		'log' => array
		(
			'callback' => array('Automator', 'purgeSystemLog'),
			'affected' => array('tl_log')
		)
	),
	'folders' => array
	(
		'images' => array
		(
			'callback' => array('Automator', 'purgeImageCache'),
			'affected' => array(System::getContainer()->getParameter('contao.image.target_path'))
		),
		'scripts' => array
		(
			'callback' => array('Automator', 'purgeScriptCache'),
			'affected' => array('assets/js', 'assets/css')
		),
		'pages' => array
		(
			'callback' => array('Automator', 'purgePageCache'),
			'affected' => array('%s/http_cache')
		),
		'search' => array
		(
			'callback' => array('Automator', 'purgeSearchCache'),
			'affected' => array('%s/contao/search')
		),
		'temp' => array
		(
			'callback' => array('Automator', 'purgeTempFolder'),
			'affected' => array('system/tmp')
		)
	),
	'custom' => array
	(
		'xml' => array
		(
			'callback' => array('Automator', 'generateXmlFiles')
		),
		'symlinks' => array
		(
			'callback' => array('Automator', 'generateSymlinks')
		)
	)
);


/**
 * Image crop modes
 */
$GLOBALS['TL_CROP'] = array
(
	'relative' => array
	(
		'proportional', 'box'
	),
	'exact' => array
	(
		'crop',
		'left_top',    'center_top',    'right_top',
		'left_center', 'center_center', 'right_center',
		'left_bottom', 'center_bottom', 'right_bottom'
	)
);


/**
 * Cron jobs
 */
$GLOBALS['TL_CRON'] = array
(
	'monthly' => array(),
	'weekly' => array(),
	'daily' => array
	(
		'purgeTempFolder' => array('Automator', 'purgeTempFolder'),
		'purgeSearchCache' => array('Automator', 'purgeSearchCache'),
		'generateSitemap' => array('Automator', 'generateSitemap')
	),
	'hourly' => array(),
	'minutely' => array()
);


/**
 * Hooks
 */
$GLOBALS['TL_HOOKS'] = array
(
	'getSystemMessages' => array
	(
		array('Messages', 'versionCheck'),
		array('Messages', 'maintenanceCheck'),
		array('Messages', 'languageFallback')
	)
);


/**
 * Register the auto_item keywords
 */
$GLOBALS['TL_AUTO_ITEM'] = array('items', 'events');


/**
 * Do not index a page if one of the following parameters is set
 */
$GLOBALS['TL_NOINDEX_KEYS'] = array('id', 'file', 'token', 'day', 'month', 'year', 'page', 'PHPSESSID');


/**
 * Register the supported CSS units
 */
$GLOBALS['TL_CSS_UNITS'] = array('px', '%', 'em', 'rem', 'vw', 'vh', 'vmin', 'vmax', 'ex', 'pt', 'pc', 'in', 'cm', 'mm');


/**
 * Wrapper elements
 */
$GLOBALS['TL_WRAPPERS'] = array
(
	'start' => array
	(
		'accordionStart',
		'sliderStart'
	),
	'stop' => array
	(
		'accordionStop',
		'sliderStop'
	),
	'single' => array
	(
		'accordionSingle'
	),
	'separator' => array()
);


/**
 * Other global arrays
 */
$GLOBALS['TL_MODELS'] = array();
$GLOBALS['TL_PERMISSIONS'] = array();
