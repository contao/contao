<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_style_sheet'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_theme',
		'ctable'                      => array('tl_style'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'onload_callback' => array
		(
			array('tl_style_sheet', 'checkPermission'),
			array('tl_style_sheet', 'updateStyleSheet')
		),
		'oncopy_callback' => array
		(
			array('tl_style_sheet', 'scheduleUpdate')
		),
		'onsubmit_callback' => array
		(
			array('tl_style_sheet', 'scheduleUpdate')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'name' => 'unique'
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
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('name', 'author', 'tstamp'),
			'child_record_callback'   => array('tl_style_sheet', 'listStyleSheet')
		),
		'global_operations' => array
		(
			'import' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style_sheet']['import'],
				'href'                => 'key=import',
				'class'               => 'header_css_import',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
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
				'label'               => &$GLOBALS['TL_LANG']['tl_style_sheet']['edit'],
				'href'                => 'table=tl_style',
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style_sheet']['editheader'],
				'href'                => 'table=tl_style_sheet&amp;act=edit',
				'icon'                => 'header.svg',
				'button_callback'     => array('tl_style_sheet', 'editHeader')
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style_sheet']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style_sheet']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style_sheet']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style_sheet']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			),
			'export' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_style_sheet']['export'],
				'href'                => 'key=export',
				'icon'                => 'theme_export.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},name;{media_legend},media,mediaQuery;{vars_legend},vars;{expert_legend:hide},embedImages,cc'
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_style_sheet']['name'],
			'inputType'               => 'text',
			'exclude'                 => true,
			'search'                  => true,
			'flag'                    => 1,
			'eval'                    => array('mandatory'=>true, 'unique'=>true, 'rgxp'=>'alnum', 'maxlength'=>64, 'spaceToUnderscore'=>true, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_style_sheet', 'romanizeName')
			),
			'sql'                     => "varchar(64) NULL"
		),
		'embedImages' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style_sheet']['embedImages'],
			'inputType'               => 'text',
			'exclude'                 => true,
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'cc' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style_sheet']['cc'],
			'inputType'               => 'text',
			'exclude'                 => true,
			'search'                  => true,
			'eval'                    => array('decodeEntities'=>true, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_style_sheet', 'sanitizeCc')
			),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'media' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style_sheet']['media'],
			'inputType'               => 'checkbox',
			'exclude'                 => true,
			'filter'                  => true,
			'options'                 => array('all', 'aural', 'braille', 'embossed', 'handheld', 'print', 'projection', 'screen', 'tty', 'tv'),
			'eval'                    => array('multiple'=>true, 'mandatory'=>true, 'tl_class'=>'clr'),
			'sql'                     => "varchar(255) NOT NULL default 'a:1:{i:0;s:3:\"all\";}'"
		),
		'mediaQuery' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style_sheet']['mediaQuery'],
			'inputType'               => 'textarea',
			'exclude'                 => true,
			'search'                  => true,
			'eval'                    => array('decodeEntities'=>true, 'style'=>'height:60px'),
			'sql'                     => "text NULL"
		),
		'vars' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_style_sheet']['vars'],
			'inputType'               => 'keyValueWizard',
			'exclude'                 => true,
			'sql'                     => "text NULL"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_style_sheet extends Contao\Backend
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

		if (!$this->User->hasAccess('css', 'themes'))
		{
			throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access the style sheets module.');
		}
	}

	/**
	 * Check for modified style sheets and update them if necessary
	 */
	public function updateStyleSheet()
	{
		/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
		$objSession = Contao\System::getContainer()->get('session');

		$session = $objSession->get('style_sheet_updater');

		if (empty($session) || !\is_array($session))
		{
			return;
		}

		$this->import('Contao\StyleSheets', 'StyleSheets');

		foreach ($session as $id)
		{
			$this->StyleSheets->updateStyleSheet($id);
		}

		$objSession->set('style_sheet_updater', null);
	}

	/**
	 * Schedule a style sheet update
	 *
	 * This method is triggered when a single style sheet or multiple style
	 * sheets are modified (edit/editAll) or duplicated (copy/copyAll).
	 *
	 * @param mixed $id
	 */
	public function scheduleUpdate($id)
	{
		// The onsubmit_callback passes a DataContainer object
		if (\is_object($id))
		{
			$id = $id->id;
		}

		// Return if there is no ID
		if (!$id || Contao\Input::get('act') == 'copy')
		{
			return;
		}

		/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
		$objSession = Contao\System::getContainer()->get('session');

		// Store the ID in the session
		$session = $objSession->get('style_sheet_updater');
		$session[] = $id;
		$objSession->set('style_sheet_updater', array_unique($session));
	}

	/**
	 * List a style sheet
	 *
	 * @param array $row
	 *
	 * @return string
	 */
	public function listStyleSheet($row)
	{
		$cc = '';
		$media = Contao\StringUtil::deserialize($row['media']);

		if ($row['cc'] != '')
		{
			$cc = ' &lt;!--['. $row['cc'] .']&gt;';
		}

		if ($row['mediaQuery'] != '')
		{
			return '<div class="tl_content_left">'. $row['name'] .' <span style="color:#999;padding-left:3px">@media '. $row['mediaQuery'] . $cc .'</span>' . "</div>\n";
		}
		elseif (!empty($media) && \is_array($media))
		{
			return '<div class="tl_content_left">'. $row['name'] .' <span style="color:#999;padding-left:3px">@media '. implode(', ', $media) . $cc .'</span>' . "</div>\n";
		}
		else
		{
			return '<div class="tl_content_left">'. $row['name'] . $cc ."</div>\n";
		}
	}

	/**
	 * Romanize the file name (see #7526)
	 *
	 * @param mixed $varValue
	 *
	 * @return mixed
	 */
	public function romanizeName($varValue)
	{
		return Patchwork\Utf8::toAscii($varValue);
	}

	/**
	 * Sanitize the conditional comments field
	 *
	 * @param mixed $varValue
	 *
	 * @return mixed
	 */
	public function sanitizeCc($varValue)
	{
		if ($varValue != '')
		{
			$varValue = str_replace(array('<!--[', ']>'), '', $varValue);
		}

		return $varValue;
	}

	/**
	 * Return the edit header button
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
	public function editHeader($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->canEditFieldsOf('tl_style_sheet') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.Contao\Image::getHtml($icon, $label).'</a> ' : Contao\Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
	}
}
