<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

Contao\System::loadLanguageFile('tl_user');

$GLOBALS['TL_DCA']['tl_user_group'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
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
			'mode'                    => 1,
			'fields'                  => array('name'),
			'flag'                    => 1,
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
				'label'               => &$GLOBALS['TL_LANG']['tl_user_group']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_user_group']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_user_group']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_user_group']['toggle'],
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('tl_user_group', 'toggleIcon')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_user_group']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},name;{modules_legend},modules,themes;{pagemounts_legend},pagemounts,alpty;{filemounts_legend},filemounts,fop;{imageSizes_legend},imageSizes;{forms_legend},forms,formp;{amg_legend},amg;{alexf_legend:hide},alexf;{account_legend},disable,start,stop',
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_user_group']['name'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'modules' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['modules'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_user_group', 'getModules'),
			'reference'               => &$GLOBALS['TL_LANG']['MOD'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true),
			'sql'                     => "blob NULL"
		),
		'themes' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['themes'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'options'                 => array('css', 'modules', 'layout', 'image_sizes', 'theme_import', 'theme_export'),
			'reference'               => &$GLOBALS['TL_LANG']['MOD'],
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'pagemounts' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['pagemounts'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox'),
			'sql'                     => "blob NULL"
		),
		'alpty' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['alpty'],
			'default'                 => array('regular', 'redirect', 'forward'),
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'options'                 => array_keys($GLOBALS['TL_PTY']),
			'reference'               => &$GLOBALS['TL_LANG']['PTY'],
			'eval'                    => array('multiple'=>true, 'helpwizard'=>true),
			'sql'                     => "blob NULL"
		),
		'filemounts' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['filemounts'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox'),
			'sql'                     => "blob NULL"
		),
		'fop' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['FOP']['fop'],
			'exclude'                 => true,
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
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('multiple'=>true),
			'options_callback' => function ()
			{
				return Contao\System::getContainer()->get('contao.image.image_sizes')->getAllOptions();
			},
			'sql'                     => "blob NULL"
		),
		'forms' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['forms'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_form.title',
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'formp' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['formp'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'options'                 => array('create', 'delete'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'amg' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user']['amg'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'alexf' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user_group']['alexf'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_user_group', 'getExcludedFields'),
			'eval'                    => array('multiple'=>true, 'size'=>36),
			'sql'                     => "blob NULL"
		),
		'disable' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user_group']['disable'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'start' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user_group']['start'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_user_group']['stop'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_user_group extends Contao\Backend
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
	 * Add a warning if there are users with access to the template editor.
	 */
	public function addTemplateWarning()
	{
		if (Contao\Input::get('act') && Contao\Input::get('act') != 'select')
		{
			return;
		}

		$objResult = $this->Database->query("SELECT COUNT(*) AS cnt FROM tl_user_group WHERE modules LIKE '%\"tpl_editor\"%'");

		if ($objResult->cnt > 0)
		{
			Contao\Message::addInfo($GLOBALS['TL_LANG']['MSC']['groupTemplateEditor']);
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
		$time = Contao\Date::floorToMinute();

		$disabled = ($row['start'] !== '' && $row['start'] > $time) || ($row['stop'] !== '' && $row['stop'] < $time);

		if ($row['disable'] || $disabled)
		{
			$image .= '_';
		}

		return sprintf('<div class="list_icon" style="background-image:url(\'%ssystem/themes/%s/icons/%s.svg\')" data-icon="%s.svg" data-icon-disabled="%s.svg">%s</div>', Contao\System::getContainer()->get('contao.assets.assets_context')->getStaticUrl(), Contao\Backend::getTheme(), $image, $disabled ? $image : rtrim($image, '_'), rtrim($image, '_') . '_', $label);
	}

	/**
	 * Return all modules except profile modules
	 *
	 * @return array
	 */
	public function getModules()
	{
		$arrModules = array();

		foreach ($GLOBALS['BE_MOD'] as $k=>$v)
		{
			if (!empty($v))
			{
				if ($k == 'accounts')
				{
					unset($v['login']);
				}

				if ($k == 'system')
				{
					unset($v['undo']);
				}

				$arrModules[$k] = array_keys($v);
			}
		}

		return $arrModules;
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
		$files = Contao\System::getContainer()->get('contao.resource_finder')->findIn('dca')->depth(0)->files()->name('*.php');

		foreach ($files as $file)
		{
			if (\in_array($file->getBasename(), $processed))
			{
				continue;
			}

			$processed[] = $file->getBasename();

			$strTable = $file->getBasename('.php');

			Contao\System::loadLanguageFile($strTable);
			$this->loadDataContainer($strTable);
		}

		$arrReturn = array();

		// Get all excluded fields
		foreach ($GLOBALS['TL_DCA'] as $k=>$v)
		{
			if (\is_array($v['fields']))
			{
				foreach ($v['fields'] as $kk=>$vv)
				{
					// Hide the "admin" field if the user is not an admin (see #184)
					if ($k == 'tl_user' && $kk == 'admin' && !$this->User->isAdmin)
					{
						continue;
					}

					if ($vv['exclude'] || $vv['orig_exclude'])
					{
						$arrReturn[$k][Contao\StringUtil::specialchars($k.'::'.$kk)] = isset($vv['label'][0]) ? $vv['label'][0] . ' <span style="color:#999;padding-left:3px">[' . $kk . ']</span>' : $kk;
					}
				}
			}
		}

		ksort($arrReturn);

		return $arrReturn;
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
		if (\strlen(Contao\Input::get('tid')))
		{
			$this->toggleVisibility(Contao\Input::get('tid'), (Contao\Input::get('state') == 1), (@func_get_arg(12) ?: null));
			$this->redirect($this->getReferer());
		}

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->hasAccess('tl_user_group::disable', 'alexf'))
		{
			return '';
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.$row['disable'];

		if ($row['disable'])
		{
			$icon = 'invisible.svg';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.Contao\Image::getHtml($icon, $label, 'data-state="' . ($row['disable'] ? 0 : 1) . '"').'</a> ';
	}

	/**
	 * Disable/enable a user group
	 *
	 * @param integer              $intId
	 * @param boolean              $blnVisible
	 * @param Contao\DataContainer $dc
	 *
	 * @throws Contao\CoreBundle\Exception\AccessDeniedException
	 */
	public function toggleVisibility($intId, $blnVisible, Contao\DataContainer $dc=null)
	{
		// Set the ID and action
		Contao\Input::setGet('id', $intId);
		Contao\Input::setGet('act', 'toggle');

		if ($dc)
		{
			$dc->id = $intId; // see #8043
		}

		// Trigger the onload_callback
		if (\is_array($GLOBALS['TL_DCA']['tl_user_group']['config']['onload_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_user_group']['config']['onload_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($dc);
				}
				elseif (\is_callable($callback))
				{
					$callback($dc);
				}
			}
		}

		// Check the field access permissions
		if (!$this->User->hasAccess('tl_user_group::disable', 'alexf'))
		{
			throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to activate/deactivate user group ID ' . $intId . '.');
		}

		// Set the current record
		if ($dc)
		{
			$objRow = $this->Database->prepare("SELECT * FROM tl_user_group WHERE id=?")
									 ->limit(1)
									 ->execute($intId);

			if ($objRow->numRows)
			{
				$dc->activeRecord = $objRow;
			}
		}

		$objVersions = new Contao\Versions('tl_user_group', $intId);
		$objVersions->initialize();

		// Reverse the logic (user groups have disable=1)
		$blnVisible = !$blnVisible;

		// Trigger the save_callback
		if (\is_array($GLOBALS['TL_DCA']['tl_user_group']['fields']['disable']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_user_group']['fields']['disable']['save_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
				}
				elseif (\is_callable($callback))
				{
					$blnVisible = $callback($blnVisible, $dc);
				}
			}
		}

		// Trigger the onsubmit_callback
		if (\is_array($GLOBALS['TL_DCA']['tl_user_group']['config']['onsubmit_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_user_group']['config']['onsubmit_callback'] as $callback)
			{
				if (\is_array($callback))
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($dc);
				}
				elseif (\is_callable($callback))
				{
					$callback($dc);
				}
			}
		}

		$time = time();

		// Update the database
		$this->Database->prepare("UPDATE tl_user_group SET tstamp=$time, disable='" . ($blnVisible ? '1' : '') . "' WHERE id=?")
					   ->execute($intId);

		if ($dc)
		{
			$dc->activeRecord->tstamp = $time;
			$dc->activeRecord->disable = ($blnVisible ? '1' : '');
		}

		$objVersions->create();
	}
}
