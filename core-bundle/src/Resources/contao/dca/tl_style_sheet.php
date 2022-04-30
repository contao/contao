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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\StyleSheets;
use Contao\System;
use Symfony\Component\String\UnicodeString;

$GLOBALS['TL_DCA']['tl_style_sheet'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
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
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('name'),
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('name', 'author', 'tstamp'),
			'child_record_callback'   => array('tl_style_sheet', 'listStyleSheet')
		),
		'global_operations' => array
		(
			'import' => array
			(
				'href'                => 'key=import',
				'class'               => 'header_css_import',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
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
				'href'                => 'table=tl_style',
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'href'                => 'table=tl_style_sheet&amp;act=edit',
				'icon'                => 'header.svg',
				'button_callback'     => array('tl_style_sheet', 'editHeader')
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
			),
			'export' => array
			(
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
			'inputType'               => 'text',
			'exclude'                 => true,
			'search'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'eval'                    => array('mandatory'=>true, 'unique'=>true, 'rgxp'=>'alnum', 'maxlength'=>64, 'spaceToUnderscore'=>true, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_style_sheet', 'romanizeName')
			),
			'sql'                     => "varchar(64) NULL"
		),
		'embedImages' => array
		(
			'inputType'               => 'text',
			'exclude'                 => true,
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'cc' => array
		(
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
			'inputType'               => 'checkbox',
			'exclude'                 => true,
			'filter'                  => true,
			'options'                 => array('all', 'aural', 'braille', 'embossed', 'handheld', 'print', 'projection', 'screen', 'tty', 'tv'),
			'eval'                    => array('multiple'=>true, 'mandatory'=>true, 'tl_class'=>'clr'),
			'sql'                     => "varchar(255) NOT NULL default 'a:1:{i:0;s:3:\"all\";}'"
		),
		'mediaQuery' => array
		(
			'inputType'               => 'textarea',
			'exclude'                 => true,
			'search'                  => true,
			'eval'                    => array('decodeEntities'=>true, 'style'=>'height:60px'),
			'sql'                     => "text NULL"
		),
		'vars' => array
		(
			'inputType'               => 'keyValueWizard',
			'exclude'                 => true,
			'sql'                     => "text NULL"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_style_sheet extends Backend
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
		trigger_deprecation('contao/core-bundle', '4.13', 'The internal CSS editor has been deprecated. Use external style sheets instead.');

		Message::addInfo($GLOBALS['TL_LANG']['MSC']['internalCssEditor']);

		if ($this->User->isAdmin)
		{
			return;
		}

		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_STYLE_SHEETS))
		{
			throw new AccessDeniedException('Not enough permissions to access the style sheets module.');
		}
	}

	/**
	 * Check for modified style sheets and update them if necessary
	 */
	public function updateStyleSheet()
	{
		$objSession = System::getContainer()->get('session');
		$session = $objSession->get('style_sheet_updater');

		if (empty($session) || !is_array($session))
		{
			return;
		}

		$this->import(StyleSheets::class, 'StyleSheets');

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
		if (is_object($id))
		{
			$id = $id->id;
		}

		// Return if there is no ID
		if (!$id || Input::get('act') == 'copy')
		{
			return;
		}

		$objSession = System::getContainer()->get('session');

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
		$media = StringUtil::deserialize($row['media']);

		if ($row['cc'])
		{
			$cc = ' &lt;!--[' . $row['cc'] . ']&gt;';
		}

		if ($row['mediaQuery'])
		{
			return '<div class="tl_content_left">' . $row['name'] . ' <span style="color:#999;padding-left:3px">@media ' . $row['mediaQuery'] . $cc . '</span>' . "</div>\n";
		}

		if (!empty($media) && is_array($media))
		{
			return '<div class="tl_content_left">' . $row['name'] . ' <span style="color:#999;padding-left:3px">@media ' . implode(', ', $media) . $cc . '</span>' . "</div>\n";
		}

		return '<div class="tl_content_left">' . $row['name'] . $cc . "</div>\n";
	}

	/**
	 * Romanize the file name (see #7526)
	 *
	 * @param mixed $varValue
	 *
	 * @return string
	 */
	public function romanizeName($varValue)
	{
		return (new UnicodeString($varValue))->ascii()->toString();
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
		if ($varValue)
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
		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_style_sheet') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}
}
