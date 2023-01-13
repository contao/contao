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
use Contao\System;

System::loadLanguageFile('tl_image_size');

$GLOBALS['TL_DCA']['tl_image_size_item'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_image_size',
		'enableVersioning'            => true,
		'onload_callback' => array
		(
			array('tl_image_size_item', 'checkPermission')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('sorting'),
			'panelLayout'             => 'limit',
			'headerFields'            => array('name', 'tstamp', 'width', 'height', 'resizeMode', 'zoom'),
			'child_record_callback'   => array('tl_image_size_item', 'listImageSizeItem')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},media,width,height,resizeMode,zoom;{source_legend},densities,sizes;{visibility_legend:hide},invisible',
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
			'foreignKey'              => 'tl_image_size.name',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'sorting' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'media' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'long', 'decodeEntities'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'densities' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_image_size']['densities'],
			'inputType'               => 'text',
			'explanation'             => 'imageSizeDensities',
			'eval'                    => array('helpwizard'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'sizes' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_image_size']['sizes'],
			'inputType'               => 'text',
			'explanation'             => 'imageSizeDensities',
			'eval'                    => array('helpwizard'=>true, 'maxlength'=>255, 'tl_class'=>'clr', 'decodeEntities'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'width' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_image_size']['width'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'clr w50'),
			'sql'                     => "int(10) NULL"
		),
		'height' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_image_size']['height'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) NULL"
		),
		'resizeMode' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_image_size']['resizeMode'],
			'inputType'               => 'select',
			'options'                 => array('proportional', 'box', 'crop'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_image_size'],
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'zoom' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_image_size']['zoom'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'prcnt', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) NULL"
		),
		'invisible' => array
		(
			'reverseToggle'           => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => false)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_image_size_item extends Backend
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

		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZES))
		{
			throw new AccessDeniedException('Not enough permissions to access the image sizes module.');
		}
	}

	/**
	 * List an image size item
	 *
	 * @param array $row
	 *
	 * @return string
	 */
	public function listImageSizeItem($row)
	{
		$html = '<div class="tl_content_left">';
		$html .= $row['media'];

		if ($row['width'] || $row['height'])
		{
			$html .= ' <span style="padding-left:3px">' . $row['width'] . 'x' . $row['height'] . '</span>';
		}

		if ($row['zoom'])
		{
			$html .= ' <span style="color:#999;padding-left:3px">(' . $row['zoom'] . '%)</span>';
		}

		$html .= "</div>\n";

		return $html;
	}
}
