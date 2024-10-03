<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FilesModel;
use Contao\Folder;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_theme'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_module', 'tl_layout', 'tl_image_size'),
		'notCopyable'                 => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('name'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'             => 'sort,search,limit',
			'defaultSearchField'      => 'name'
		),
		'label' => array
		(
			'fields'                  => array('name'),
			'format'                  => '%s',
			'label_callback'          => array('tl_theme', 'addPreviewImage')
		),
		'global_operations' => array
		(
			'importTheme' => array
			(
				'href'                => 'key=importTheme',
				'class'               => 'header_theme_import',
				'button_callback'     => array('tl_theme', 'importTheme')
			),
			'store' => array
			(
				'href'                => 'key=themeStore',
				'class'               => 'header_store',
				'button_callback'     => array('tl_theme', 'themeStore')
			),
			'all'
		),
		'operations' => array
		(
			'edit',
			'delete',
			'modules' => array
			(
				'href'                => 'table=tl_module',
				'prefetch'            => true,
				'icon'                => 'modules.svg',
			),
			'layout' => array
			(
				'href'                => 'table=tl_layout',
				'prefetch'            => true,
				'icon'                => 'layout.svg',
			),
			'imageSizes' => array
			(
				'href'                => 'table=tl_image_size',
				'prefetch'            => true,
				'icon'                => 'sizes.svg',
			),
			'show',
			'exportTheme' => array
			(
				'href'                => 'key=exportTheme',
				'icon'                => 'theme_export.svg',
				'button_callback'     => array('tl_theme', 'exportTheme')
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},name,author;{config_legend},folders,screenshot,templates;{vars_legend},vars'
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
			'inputType'               => 'text',
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'unique'=>true, 'decodeEntities'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'author' => array
		(
			'inputType'               => 'text',
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'folders' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('multiple'=>true, 'fieldType'=>'checkbox'),
			'sql'                     => "blob NULL"
		),
		'screenshot' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'isGallery'=>true, 'extensions'=>'%contao.image.valid_extensions%'),
			'sql'                     => "binary(16) NULL"
		),
		'templates' => array
		(
			'inputType'               => 'select',
			'options_callback'        => array('tl_theme', 'getTemplateFolders'),
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_theme extends Backend
{
	/**
	 * Add an image to each record
	 *
	 * @param array  $row
	 * @param string $label
	 *
	 * @return string
	 */
	public function addPreviewImage($row, $label)
	{
		if ($row['screenshot'])
		{
			$objFile = FilesModel::findByUuid($row['screenshot']);
			$projectDir = System::getContainer()->getParameter('kernel.project_dir');

			if ($objFile !== null && file_exists($projectDir . '/' . $objFile->path))
			{
				$label = Image::getHtml(System::getContainer()->get('contao.image.factory')->create($projectDir . '/' . $objFile->path, array(75, 50, 'center_top'))->getUrl($projectDir), '', 'class="theme_preview"') . ' ' . $label;
			}
		}

		return $label;
	}

	/**
	 * Return all template folders as array
	 *
	 * @return array
	 */
	public function getTemplateFolders()
	{
		return $this->doGetTemplateFolders('templates');
	}

	/**
	 * Return all template folders as array
	 *
	 * @param string  $path
	 * @param integer $level
	 *
	 * @return array
	 */
	protected function doGetTemplateFolders($path, $level=0)
	{
		$return = array();
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		foreach (Folder::scan($projectDir . '/' . $path) as $file)
		{
			if (is_dir($projectDir . '/' . $path . '/' . $file))
			{
				$return[$path . '/' . $file] = str_repeat(' &nbsp; &nbsp; ', $level) . $file;
				$return = array_merge($return, $this->doGetTemplateFolders($path . '/' . $file, $level+1));
			}
		}

		return $return;
	}

	/**
	 * Return the "import theme" link
	 *
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $class
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function importTheme($href, $label, $title, $class, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_IMPORT_THEMES) ? '<a href="' . $this->addToUrl($href) . '" class="' . $class . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . $label . '</a> ' : '';
	}

	/**
	 * Return the theme store link
	 *
	 * @return string
	 */
	public function themeStore()
	{
		return '<a href="https://themes.contao.org" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['tl_theme']['store'][1]) . '" class="header_store" target="_blank" rel="noreferrer noopener">' . $GLOBALS['TL_LANG']['tl_theme']['store'][0] . '</a>';
	}

	/**
	 * Return the "export theme" button
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
	public function exportTheme($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EXPORT_THEMES) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(str_replace('.svg', '--disabled.svg', $icon)) . ' ';
	}
}
