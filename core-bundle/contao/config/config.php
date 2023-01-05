<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ArticleModel;
use Contao\Automator;
use Contao\CheckBox;
use Contao\CheckBoxWizard;
use Contao\ChmodTable;
use Contao\ContentAccordion;
use Contao\ContentAccordionStart;
use Contao\ContentAccordionStop;
use Contao\ContentAlias;
use Contao\ContentArticle;
use Contao\ContentModel;
use Contao\ContentModule;
use Contao\ContentSliderStart;
use Contao\ContentSliderStop;
use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\Crawl;
use Contao\FilesModel;
use Contao\FileTree;
use Contao\Form;
use Contao\FormCaptcha;
use Contao\FormCheckbox;
use Contao\FormExplanation;
use Contao\FormFieldModel;
use Contao\FormFieldsetStart;
use Contao\FormFieldsetStop;
use Contao\FormHidden;
use Contao\FormHtml;
use Contao\FormModel;
use Contao\FormPassword;
use Contao\FormRadio;
use Contao\FormRange;
use Contao\FormSelect;
use Contao\FormSubmit;
use Contao\FormText;
use Contao\FormTextarea;
use Contao\FormUpload;
use Contao\ImageSize;
use Contao\ImageSizeItemModel;
use Contao\ImageSizeModel;
use Contao\InputUnit;
use Contao\KeyValueWizard;
use Contao\LayoutModel;
use Contao\ListWizard;
use Contao\MemberGroupModel;
use Contao\MemberModel;
use Contao\Messages;
use Contao\MetaWizard;
use Contao\ModuleArticleList;
use Contao\ModuleArticlenav;
use Contao\ModuleBooknav;
use Contao\ModuleBreadcrumb;
use Contao\ModuleChangePassword;
use Contao\ModuleCloseAccount;
use Contao\ModuleCustomnav;
use Contao\ModuleHtml;
use Contao\ModuleLogin;
use Contao\ModuleLostPassword;
use Contao\ModuleMaintenance;
use Contao\ModuleModel;
use Contao\ModuleNavigation;
use Contao\ModulePersonalData;
use Contao\ModuleQuicklink;
use Contao\ModuleQuicknav;
use Contao\ModuleRandomImage;
use Contao\ModuleRegistration;
use Contao\ModuleRssReader;
use Contao\ModuleSearch;
use Contao\ModuleSitemap;
use Contao\ModuleTwoFactor;
use Contao\ModuleWizard;
use Contao\OptInModel;
use Contao\OptionWizard;
use Contao\PageError401;
use Contao\PageError403;
use Contao\PageError404;
use Contao\PageForward;
use Contao\PageLogout;
use Contao\PageModel;
use Contao\PageRedirect;
use Contao\PageRegular;
use Contao\PageTree;
use Contao\Password;
use Contao\Picker;
use Contao\PurgeData;
use Contao\RadioButton;
use Contao\RadioTable;
use Contao\RootPageDependentSelect;
use Contao\SectionWizard;
use Contao\SelectMenu;
use Contao\SerpPreview;
use Contao\StringUtil;
use Contao\System;
use Contao\TableWizard;
use Contao\TextArea;
use Contao\TextField;
use Contao\Theme;
use Contao\ThemeModel;
use Contao\TimePeriod;
use Contao\TrblField;
use Contao\Upload;
use Contao\UserGroupModel;
use Contao\UserModel;

// Back end modules
$GLOBALS['BE_MOD'] = array
(
	// Content modules
	'content' => array
	(
		'page' => array
		(
			'tables'      => array('tl_page')
		),
		'article' => array
		(
			'tables'      => array('tl_article', 'tl_content'),
			'table'       => array(BackendCsvImportController::class, 'importTableWizardAction'),
			'list'        => array(BackendCsvImportController::class, 'importListWizardAction')
		),
		'files' => array
		(
			'tables'                  => array('tl_files')
		),
		'form' => array
		(
			'tables'      => array('tl_form', 'tl_form_field'),
			'option'      => array(BackendCsvImportController::class, 'importOptionWizardAction')
		)
	),

	// Design modules
	'design' => array
	(
		'themes' => array
		(
			'tables'      => array('tl_theme', 'tl_module', 'tl_layout', 'tl_image_size', 'tl_image_size_item'),
			'importTheme' => array(Theme::class, 'importTheme'),
			'exportTheme' => array(Theme::class, 'exportTheme'),
		),
		'tpl_editor' => array
		(
			'tables'      => array('tl_templates'),
			'new_tpl'     => array('tl_templates', 'addNewTemplate'),
			'compare'     => array('tl_templates', 'compareTemplate')
		)
	),

	// Account modules
	'accounts' => array
	(
		'member' => array
		(
			'tables'                  => array('tl_member')
		),
		'mgroup' => array
		(
			'tables'                  => array('tl_member_group')
		),
		'user' => array
		(
			'tables'                  => array('tl_user')
		),
		'group' => array
		(
			'tables'                  => array('tl_user_group')
		),
		'login' => array
		(
			'tables'                  => array('tl_user'),
			'hideInNavigation'        => true,
			'disablePermissionChecks' => true
		),
		'security' => array
		(
			'callback'                => ModuleTwoFactor::class,
			'hideInNavigation'        => true,
			'disablePermissionChecks' => true
		),
		'favorites' => array
		(
			'tables'                  => array('tl_favorites'),
			'hideInNavigation'        => true,
			'disablePermissionChecks' => true
		)
	),

	// System modules
	'system' => array
	(
		'settings' => array
		(
			'tables'                  => array('tl_settings')
		),
		'maintenance' => array
		(
			'callback'                => ModuleMaintenance::class
		),
		'log' => array
		(
			'tables'                  => array('tl_log')
		),
		'preview_link' => array
		(
			'tables'                  => array('tl_preview_link'),
		),
		'opt_in' => array
		(
			'tables'                  => array('tl_opt_in'),
			'resend'                  => array('tl_opt_in', 'resendToken'),
		),
		'undo' => array
		(
			'tables'                  => array('tl_undo'),
			'disablePermissionChecks' => true
		)
	)
);

// Front end modules
$GLOBALS['FE_MOD'] = array
(
	'navigationMenu' => array(
		'navigation'     => ModuleNavigation::class,
		'customnav'      => ModuleCustomnav::class,
		'breadcrumb'     => ModuleBreadcrumb::class,
		'quicknav'       => ModuleQuicknav::class,
		'quicklink'      => ModuleQuicklink::class,
		'booknav'        => ModuleBooknav::class,
		'articlenav'     => ModuleArticlenav::class,
		'sitemap'        => ModuleSitemap::class
	),
	'user' => array
	(
		'login'          => ModuleLogin::class,
		'personalData'   => ModulePersonalData::class,
		'registration'   => ModuleRegistration::class,
		'changePassword' => ModuleChangePassword::class,
		'lostPassword'   => ModuleLostPassword::class,
		'closeAccount'   => ModuleCloseAccount::class
	),
	'application' => array
	(
		'form'           => Form::class,
		'search'         => ModuleSearch::class
	),
	'miscellaneous' => array
	(
		'articlelist'    => ModuleArticleList::class,
		'randomImage'    => ModuleRandomImage::class,
		'html'           => ModuleHtml::class,
		'rssReader'      => ModuleRssReader::class,
	)
);

// Content elements
$GLOBALS['TL_CTE'] = array
(
	'accordion' => array
	(
		'accordionSingle' => ContentAccordion::class,
		'accordionStart'  => ContentAccordionStart::class,
		'accordionStop'   => ContentAccordionStop::class
	),
	'slider' => array
	(
		'sliderStart'     => ContentSliderStart::class,
		'sliderStop'      => ContentSliderStop::class
	),
	'includes' => array
	(
		'article'         => ContentArticle::class,
		'alias'           => ContentAlias::class,
		'form'            => Form::class,
		'module'          => ContentModule::class,
	)
);

// Back end form fields
$GLOBALS['BE_FFL'] = array
(
	'text'                    => TextField::class,
	'password'                => Password::class,
	'textarea'                => TextArea::class,
	'select'                  => SelectMenu::class,
	'checkbox'                => CheckBox::class,
	'checkboxWizard'          => CheckBoxWizard::class,
	'radio'                   => RadioButton::class,
	'radioTable'              => RadioTable::class,
	'inputUnit'               => InputUnit::class,
	'trbl'                    => TrblField::class,
	'chmod'                   => ChmodTable::class,
	'picker'                  => Picker::class,
	'pageTree'                => PageTree::class,
	'fileTree'                => FileTree::class,
	'fileUpload'              => Upload::class,
	'tableWizard'             => TableWizard::class,
	'listWizard'              => ListWizard::class,
	'optionWizard'            => OptionWizard::class,
	'moduleWizard'            => ModuleWizard::class,
	'keyValueWizard'          => KeyValueWizard::class,
	'imageSize'               => ImageSize::class,
	'timePeriod'              => TimePeriod::class,
	'metaWizard'              => MetaWizard::class,
	'sectionWizard'           => SectionWizard::class,
	'serpPreview'             => SerpPreview::class,
	'rootPageDependentSelect' => RootPageDependentSelect::class
);

// Front end form fields
$GLOBALS['TL_FFL'] = array
(
	'explanation'   => FormExplanation::class,
	'html'          => FormHtml::class,
	'fieldsetStart' => FormFieldsetStart::class,
	'fieldsetStop'  => FormFieldsetStop::class,
	'text'          => FormText::class,
	'password'      => FormPassword::class,
	'textarea'      => FormTextarea::class,
	'select'        => FormSelect::class,
	'radio'         => FormRadio::class,
	'checkbox'      => FormCheckbox::class,
	'upload'        => FormUpload::class,
	'range'         => FormRange::class,
	'hidden'        => FormHidden::class,
	'captcha'       => FormCaptcha::class,
	'submit'        => FormSubmit::class,
);

// Page types
$GLOBALS['TL_PTY'] = array
(
	'regular'   => PageRegular::class,
	'forward'   => PageForward::class,
	'redirect'  => PageRedirect::class,
	'logout'    => PageLogout::class,
	'error_401' => PageError401::class,
	'error_403' => PageError403::class,
	'error_404' => PageError404::class
);

// Maintenance
$GLOBALS['TL_MAINTENANCE'] = array
(
	Crawl::class,
	PurgeData::class
);

// Purge jobs
$GLOBALS['TL_PURGE'] = array
(
	'tables' => array
	(
		'index' => array
		(
			'callback' => array(Automator::class, 'purgeSearchTables'),
			'affected' => array('tl_search', 'tl_search_index', 'tl_search_term')
		),
		'undo' => array
		(
			'callback' => array(Automator::class, 'purgeUndoTable'),
			'affected' => array('tl_undo')
		),
		'versions' => array
		(
			'callback' => array(Automator::class, 'purgeVersionTable'),
			'affected' => array('tl_version')
		),
		'log' => array
		(
			'callback' => array(Automator::class, 'purgeSystemLog'),
			'affected' => array('tl_log')
		),
		'crawl_queue' => array
		(
			'callback' => array(Automator::class, 'purgeCrawlQueue'),
			'affected' => array('tl_crawl_queue')
		)
	),
	'folders' => array
	(
		'images' => array
		(
			'callback' => array(Automator::class, 'purgeImageCache'),
			'affected' => array(StringUtil::stripRootDir(System::getContainer()->getParameter('contao.image.target_dir')))
		),
		'previews' => array
		(
			'callback' => array(Automator::class, 'purgePreviewCache'),
			'affected' => array(StringUtil::stripRootDir(System::getContainer()->getParameter('contao.image.preview.target_dir')))
		),
		'scripts' => array
		(
			'callback' => array(Automator::class, 'purgeScriptCache'),
			'affected' => array('assets/js', 'assets/css')
		),
		'temp' => array
		(
			'callback' => array(Automator::class, 'purgeTempFolder'),
			'affected' => array('system/tmp')
		)
	),
	'custom' => array
	(
		'pages' => array
		(
			'callback' => array(Automator::class, 'purgePageCache'),
		),
		'xml' => array
		(
			'callback' => array(Automator::class, 'generateXmlFiles')
		),
		'symlinks' => array
		(
			'callback' => array(Automator::class, 'generateSymlinks')
		)
	)
);

// Hooks
$GLOBALS['TL_HOOKS'] = array
(
	'getSystemMessages' => array
	(
		array(Messages::class, 'languageFallback')
	)
);

// Wrapper elements
$GLOBALS['TL_WRAPPERS'] = array
(
	'start' => array
	(
		'accordionStart',
		'sliderStart',
		'fieldsetStart'
	),
	'stop' => array
	(
		'accordionStop',
		'sliderStop',
		'fieldsetStop'
	),
	'single' => array
	(
		'accordionSingle'
	),
	'separator' => array()
);

// Models
$GLOBALS['TL_MODELS'] = array(
	'tl_article' => ArticleModel::class,
	'tl_content' => ContentModel::class,
	'tl_files' => FilesModel::class,
	'tl_form_field' => FormFieldModel::class,
	'tl_form' => FormModel::class,
	'tl_image_size_item' => ImageSizeItemModel::class,
	'tl_image_size' => ImageSizeModel::class,
	'tl_layout' => LayoutModel::class,
	'tl_member_group' => MemberGroupModel::class,
	'tl_member' => MemberModel::class,
	'tl_module' => ModuleModel::class,
	'tl_opt_in' => OptInModel::class,
	'tl_page' => PageModel::class,
	'tl_theme' => ThemeModel::class,
	'tl_user_group' => UserGroupModel::class,
	'tl_user' => UserModel::class
);

// Other global arrays
$GLOBALS['TL_PERMISSIONS'] = array();
