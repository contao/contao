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
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_user'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'onload_callback' => array
		(
			array('tl_user', 'handleUserProfile'),
			array('tl_user', 'addTemplateWarning')
		),
		'onsubmit_callback' => array
		(
			array('tl_user', 'storeDateAdded'),
			array('tl_user', 'updateCurrentUser')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'username' => 'unique',
				'email' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('dateAdded'),
			'panelLayout'             => 'filter;sort,search,limit',
			'defaultSearchField'      => 'name'
		),
		'label' => array
		(
			'fields'                  => array('', 'name', 'username', 'dateAdded'),
			'showColumns'             => true,
			'label_callback'          => array('tl_user', 'addIcon')
		),
		'operations' => array
		(
			'edit',
			'copy',
			'delete',
			'toggle',
			'show',
			'su' => array
			(
				'href'                => 'key=su',
				'icon'                => 'su.svg',
				'button_callback'     => array('tl_user', 'switchUser')
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('inherit', 'admin'),
		'login'                       => '{name_legend},name,email;{backend_legend},language,uploader,showHelp,thumbnails,useRTE,useCE,doNotCollapse;{session_legend},session;{password_legend},password;{theme_legend:hide},backendTheme',
		'admin'                       => '{name_legend},username,name,email;{backend_legend:hide},language,uploader,showHelp,thumbnails,useRTE,useCE,doNotCollapse;{theme_legend:hide},backendTheme;{password_legend:hide},password,pwChange;{admin_legend},admin;{account_legend},disable,start,stop',
		'default'                     => '{name_legend},username,name,email;{backend_legend:hide},language,uploader,showHelp,thumbnails,useRTE,useCE,doNotCollapse;{theme_legend:hide},backendTheme;{password_legend:hide},password,pwChange;{admin_legend},admin;{groups_legend},groups,inherit;{account_legend},disable,start,stop',
		'group'                       => '{name_legend},username,name,email;{backend_legend:hide},language,uploader,showHelp,thumbnails,useRTE,useCE,doNotCollapse;{theme_legend:hide},backendTheme;{password_legend:hide},password,pwChange;{admin_legend},admin;{groups_legend},groups,inherit;{account_legend},disable,start,stop',
		'extend'                      => '{name_legend},username,name,email;{backend_legend:hide},language,uploader,showHelp,thumbnails,useRTE,useCE,doNotCollapse;{theme_legend:hide},backendTheme;{password_legend:hide},password,pwChange;{admin_legend},admin;{groups_legend},groups,inherit;{modules_legend},modules,themes,frontendModules;{elements_legend},elements,fields;{pagemounts_legend},pagemounts,alpty;{filemounts_legend},filemounts,fop;{imageSizes_legend},imageSizes;{forms_legend},forms,formp;{amg_legend},amg;{account_legend},disable,start,stop',
		'custom'                      => '{name_legend},username,name,email;{backend_legend:hide},language,uploader,showHelp,thumbnails,useRTE,useCE,doNotCollapse;{theme_legend:hide},backendTheme;{password_legend:hide},password,pwChange;{admin_legend},admin;{groups_legend},groups,inherit;{modules_legend},modules,themes,frontendModules;{elements_legend},elements,fields;{pagemounts_legend},pagemounts,alpty;{filemounts_legend},filemounts,fop;{imageSizes_legend},imageSizes;{forms_legend},forms,formp;{amg_legend},amg;{account_legend},disable,start,stop'
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
		'username' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'extnd', 'nospace'=>true, 'unique'=>true, 'maxlength'=>64, 'tl_class'=>'w50', 'autocapitalize'=>'off', 'autocomplete'=>'username'),
			'sql'                     => "varchar(64) BINARY NULL"
		),
		'name' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'email' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'email', 'maxlength'=>255, 'unique'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'language' => array
		(
			'default'                 => LocaleUtil::formatAsLocale($GLOBALS['TL_LANGUAGE']),
			'filter'                  => true,
			'inputType'               => 'select',
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
			'options_callback'        => static fn () => System::getContainer()->get('contao.intl.locales')->getEnabledLocales(null, Input::get('do') != 'user'),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'backendTheme' => array
		(
			'inputType'               => 'select',
			'options_callback' => static function () {
				return Backend::getThemes();
			},
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'uploader' => array
		(
			'inputType'               => 'select',
			'options'                 => array('DropZone', 'FileUpload'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_user'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'showHelp' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => true)
		),
		'thumbnails' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => true)
		),
		'useRTE' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => true)
		),
		'useCE' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => true)
		),
		'doNotCollapse' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'password' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['password'],
			'inputType'               => 'password',
			'eval'                    => array('mandatory'=>true, 'preserveTags'=>true, 'minlength'=>Config::get('minPasswordLength'), 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'pwChange' => array
		(
			'inputType'               => 'checkbox',
			'filter'                  => true,
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'admin' => array
		(
			'exclude'                 => false,
			'inputType'               => 'checkbox',
			'filter'                  => true,
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'groups' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkboxWizard',
			'foreignKey'              => 'tl_user_group.name',
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'belongsToMany', 'load'=>'lazy')
		),
		'inherit' => array
		(
			'inputType'               => 'radio',
			'options'                 => array('group', 'extend', 'custom'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_user'],
			'eval'                    => array('helpwizard'=>true, 'submitOnChange'=>true),
			'sql'                     => "varchar(12) NOT NULL default 'group'"
		),
		'modules' => array
		(
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_user', 'getModules'),
			'reference'               => &$GLOBALS['TL_LANG']['MOD'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true, 'collapseUncheckedGroups'=>true),
			'sql'                     => "blob NULL"
		),
		'themes' => array
		(
			'inputType'               => 'checkbox',
			'options'                 => array('modules', 'layout', 'image_sizes', 'theme_import', 'theme_export'),
			'reference'               => &$GLOBALS['TL_LANG']['MOD'],
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'elements' => array
		(
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_user', 'getContentElements'),
			'reference'               => &$GLOBALS['TL_LANG']['CTE'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true, 'collapseUncheckedGroups'=>true),
			'sql'                     => "blob NULL"
		),
		'fields' => array
		(
			'inputType'               => 'checkbox',
			'options'                 => array_keys($GLOBALS['TL_FFL']),
			'reference'               => &$GLOBALS['TL_LANG']['FFL'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true),
			'sql'                     => "blob NULL"
		),
		'frontendModules' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'reference'               => &$GLOBALS['TL_LANG']['FMD'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true, 'collapseUncheckedGroups'=>true),
			'sql'                     => "blob NULL"
		),
		'pagemounts' => array
		(
			'inputType'               => 'pageTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox'),
			'sql'                     => "blob NULL"
		),
		'alpty' => array
		(
			'default'                 => array('regular', 'redirect', 'forward'),
			'inputType'               => 'checkbox',
			'reference'               => &$GLOBALS['TL_LANG']['PTY'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true),
			'sql'                     => "blob NULL"
		),
		'filemounts' => array
		(
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
			'inputType'               => 'checkbox',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('multiple'=>true, 'collapseUncheckedGroups'=>true),
			'options_callback' => static function () {
				return System::getContainer()->get('contao.image.sizes')->getAllOptions();
			},
			'sql'                     => "blob NULL"
		),
		'forms' => array
		(
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_form.title',
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'formp' => array
		(
			'inputType'               => 'checkbox',
			'options'                 => array('create', 'delete'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'amg' => array
		(
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'disable' => array
		(
			'reverseToggle'           => true,
			'filter'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_DESC,
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
		),
		'session' => array
		(
			'input_field_callback'    => array('tl_user', 'sessionField'),
			'eval'                    => array('doNotShow'=>true, 'doNotCopy'=>true),
			'sql'                     => "blob NULL"
		),
		'dateAdded' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
			'default'                 => time(),
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'eval'                    => array('rgxp'=>'datim', 'doNotCopy'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'secret' => array
		(
			'eval'                    => array('doNotShow'=>true, 'doNotCopy'=>true),
			'sql'                     => "binary(128) NULL default NULL"
		),
		'useTwoFactor' => array
		(
			'eval'                    => array('isBoolean'=>true, 'doNotCopy'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'lastLogin' => array
		(
			'eval'                    => array('rgxp'=>'datim', 'doNotShow'=>true, 'doNotCopy'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'currentLogin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['lastLogin'],
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'eval'                    => array('rgxp'=>'datim', 'doNotCopy'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'backupCodes' => array
		(
			'eval'                    => array('doNotCopy'=>true, 'doNotShow'=>true),
			'sql'                     => "text NULL"
		),
		'trustedTokenVersion' => array
		(
			'eval'                    => array('doNotCopy'=>true, 'doNotShow'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_user extends Backend
{
	/**
	 * @var int
	 */
	private static $origUserId;

	/**
	 * Handle the profile page.
	 *
	 * @param DataContainer $dc
	 */
	public function handleUserProfile(DataContainer $dc)
	{
		if (Input::get('do') != 'login')
		{
			return;
		}

		// Should not happen because of the redirect but better safe than sorry
		if (Input::get('act') != 'edit' || BackendUser::getInstance()->id != Input::get('id'))
		{
			throw new AccessDeniedException('Not allowed to edit this page.');
		}

		$GLOBALS['TL_DCA'][$dc->table]['config']['closed'] = true;
		$GLOBALS['TL_DCA'][$dc->table]['config']['hideVersionMenu'] = true;

		$GLOBALS['TL_DCA'][$dc->table]['palettes'] = array
		(
			'__selector__' => $GLOBALS['TL_DCA'][$dc->table]['palettes']['__selector__'],
			'default' => $GLOBALS['TL_DCA'][$dc->table]['palettes']['login']
		);

		$arrFields = StringUtil::trimsplit('[,;]', $GLOBALS['TL_DCA'][$dc->table]['palettes']['default'] ?? '');

		foreach ($arrFields as $strField)
		{
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['exclude'] = false;
		}
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

		$objResult = Database::getInstance()->query("
			SELECT
				EXISTS(SELECT * FROM tl_user WHERE admin = 0 AND inherit != 'group' AND modules LIKE '%\"tpl_editor\"%') as showTemplateWarning,
				EXISTS(SELECT * FROM tl_user WHERE admin = 0 AND inherit != 'group' AND themes LIKE '%\"theme_import\"%') as showThemeWarning,
				EXISTS(SELECT * FROM tl_user WHERE inherit != 'group' AND elements LIKE '%\"unfiltered_html\"%') as showUnfilteredHtmlWarning
		");

		if ($objResult->showTemplateWarning)
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['userTemplateEditor']);
		}

		if ($objResult->showThemeWarning)
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['userThemeImport']);
		}

		if ($objResult->showUnfilteredHtmlWarning)
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['userUnfilteredHtml']);
		}
	}

	/**
	 * Add an image to each record
	 *
	 * @param array         $row
	 * @param string        $label
	 * @param DataContainer $dc
	 * @param array         $args
	 *
	 * @return array
	 */
	public function addIcon($row, $label, DataContainer $dc, $args)
	{
		$image = $row['admin'] ? 'admin' : 'user';
		$disabled = ($row['start'] !== '' && $row['start'] > time()) || ($row['stop'] !== '' && $row['stop'] <= time());

		if ($row['useTwoFactor'])
		{
			$image .= '_two_factor';
		}

		$icon = $image;

		if ($disabled || $row['disable'])
		{
			$image .= '--disabled';
		}

		$args[0] = sprintf(
			'<div class="list_icon_new" style="background-image:url(\'%s\')" data-icon="%s" data-icon-disabled="%s">&nbsp;</div>',
			Image::getUrl($image),
			Image::getUrl($icon),
			Image::getUrl($icon . '--disabled')
		);

		return $args;
	}

	/**
	 * Generate a "switch account" button and return it as string
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function switchUser($row, $href, $label, $title, $icon)
	{
		$security = System::getContainer()->get('security.helper');

		if (!$security->isGranted('ROLE_ALLOWED_TO_SWITCH'))
		{
			return '';
		}

		$disabled = false;

		if (BackendUser::getInstance()->id == $row['id'])
		{
			$disabled = true;
		}
		elseif ($security->isGranted('ROLE_PREVIOUS_ADMIN'))
		{
			if (self::$origUserId === null)
			{
				$origToken = $security->getToken()->getOriginalToken();
				$origUser = $origToken->getUser();

				if ($origUser instanceof BackendUser)
				{
					self::$origUserId = $origUser->id;
				}
			}

			if (self::$origUserId == $row['id'])
			{
				$disabled = true;
			}
		}

		if ($disabled)
		{
			return Image::getHtml(str_replace('.svg', '--disabled.svg', $icon)) . ' ';
		}

		$router = System::getContainer()->get('router');
		$url = $router->generate('contao_backend', array('_switch_user'=>$row['username']));

		return '<a href="' . $url . '" title="' . StringUtil::specialchars($title) . '">' . Image::getHtml($icon, $label) . '</a> ';
	}

	/**
	 * Return a checkbox to delete session data
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function sessionField(DataContainer $dc)
	{
		if (Input::post('FORM_SUBMIT') == 'tl_user')
		{
			$arrPurge = Input::post('purge');

			if (is_array($arrPurge))
			{
				$automator = new Automator();

				if (in_array('purge_session', $arrPurge))
				{
					$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
					$objSessionBag->clear();
					Message::addConfirmation($GLOBALS['TL_LANG']['tl_user']['sessionPurged']);
				}

				if (in_array('purge_images', $arrPurge))
				{
					$automator->purgeImageCache();
					Message::addConfirmation($GLOBALS['TL_LANG']['tl_user']['htmlPurged']);
				}

				if (in_array('purge_previews', $arrPurge))
				{
					$automator->purgePreviewCache();
					Message::addConfirmation($GLOBALS['TL_LANG']['tl_user']['previewPurged']);
				}

				if (in_array('purge_pages', $arrPurge))
				{
					$automator->purgePageCache();
					Message::addConfirmation($GLOBALS['TL_LANG']['tl_user']['tempPurged']);
				}

				$this->reload();
			}
		}

		return '
<div class="widget">
  <fieldset class="tl_checkbox_container">
    <legend>' . $GLOBALS['TL_LANG']['tl_user']['session'][0] . '</legend>
    <span><input type="checkbox" id="check_all_purge" class="tl_checkbox" onclick="Backend.toggleCheckboxGroup(this, \'ctrl_purge\')"> <label for="check_all_purge" class="check-all"><em>' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</em></label></span>
    <span><input type="checkbox" name="purge[]" id="opt_purge_0" class="tl_checkbox" value="purge_session" data-action="focus->contao--scroll-offset#store"> <label for="opt_purge_0">' . $GLOBALS['TL_LANG']['tl_user']['sessionLabel'] . '</label></span>
    <span><input type="checkbox" name="purge[]" id="opt_purge_1" class="tl_checkbox" value="purge_images" data-action="focus->contao--scroll-offset#store"> <label for="opt_purge_1">' . $GLOBALS['TL_LANG']['tl_user']['htmlLabel'] . '</label></span>
    <span><input type="checkbox" name="purge[]" id="opt_purge_2" class="tl_checkbox" value="purge_previews" data-action="focus->contao--scroll-offset#store"> <label for="opt_purge_2">' . $GLOBALS['TL_LANG']['tl_user']['previewLabel'] . '</label></span>
    <span><input type="checkbox" name="purge[]" id="opt_purge_3" class="tl_checkbox" value="purge_pages" data-action="focus->contao--scroll-offset#store"> <label for="opt_purge_3">' . $GLOBALS['TL_LANG']['tl_user']['tempLabel'] . '</label></span>
  </fieldset>' . $dc->help() . '
</div>';
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
		if (!BackendUser::getInstance()->isAdmin && (!is_array($modules) || !in_array('tpl_editor', $modules)) && ($key = array_search('tpl_editor', $arrModules['design'])) !== false)
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
	 * Store the date when the account has been added
	 *
	 * @param DataContainer $dc
	 */
	public function storeDateAdded(DataContainer $dc)
	{
		// Return if there is no active record (override all)
		if (!$dc->activeRecord || $dc->activeRecord->dateAdded > 0)
		{
			return;
		}

		// Fallback solution for existing accounts
		if ($dc->activeRecord->lastLogin > 0)
		{
			$time = $dc->activeRecord->lastLogin;
		}
		else
		{
			$time = time();
		}

		Database::getInstance()
			->prepare("UPDATE tl_user SET dateAdded=? WHERE id=?")
			->execute($time, $dc->id);
	}

	/**
	 * Update the current user if something changes, otherwise they would be
	 * logged out automatically
	 *
	 * @param DataContainer $dc
	 */
	public function updateCurrentUser(DataContainer $dc)
	{
		$user = BackendUser::getInstance();

		if ($user->id == $dc->id)
		{
			$user->findBy('id', $user->id);
		}
	}
}
