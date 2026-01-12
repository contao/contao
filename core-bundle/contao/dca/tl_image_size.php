<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image\ResizeOptions;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_image_size'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_theme',
		'ctable'                      => array('tl_image_size_item'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'name',
		'userRoot'                   => 'imageSizes',
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
				'tstamp' => 'index'
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
			'defaultSearchField'      => 'name',
			'headerFields'            => array('name', 'author', 'tstamp'),
		),
		'label' => array
		(
			'fields'                  => array('name'),
			'format'                  => '%s',
			'label_callback'          => array('tl_image_size', 'listImageSize'),
		),
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('preserveMetadata'),
		'default'                     => '{title_legend},name,width,height,resizeMode,zoom;{source_legend},densities,sizes;{loading_legend},lazyLoading;{metadata_legend},preserveMetadata;{expert_legend:hide},formats,skipIfDimensionsMatch,imageQuality,cssClass',
		'overwrite'                   => '{title_legend},name,width,height,resizeMode,zoom;{source_legend},densities,sizes;{loading_legend},lazyLoading;{metadata_legend},preserveMetadata,preserveMetadataFields;{expert_legend:hide},formats,skipIfDimensionsMatch,imageQuality,cssClass'
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
			'search'                  => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NULL"
		),
		'imageQuality' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'prcnt', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) NULL"
		),
		'cssClass' => array
		(
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'densities' => array
		(
			'inputType'               => 'text',
			'explanation'             => 'imageSizeDensities',
			'eval'                    => array('helpwizard'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'sizes' => array
		(
			'inputType'               => 'text',
			'explanation'             => 'imageSizeDensities',
			'eval'                    => array('helpwizard'=>true, 'maxlength'=>255, 'tl_class'=>'clr', 'decodeEntities'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'width' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'clr w50'),
			'sql'                     => "int(10) NULL"
		),
		'height' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) NULL"
		),
		'resizeMode' => array
		(
			'inputType'               => 'select',
			'options'                 => array('proportional', 'box', 'crop'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_image_size'],
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'zoom' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'prcnt', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) NULL"
		),
		'formats' => array
		(
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_image_size', 'getFormats'),
			'eval'                    => array('multiple'=>true),
			'sql'                     => "varchar(1024) NOT NULL default ''"
		),
		'preserveMetadata' => array
		(
			'inputType'               => 'radio',
			'options'                 => array('default', 'overwrite', 'delete'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_image_size']['preserveMetadataOptions'],
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "varchar(12) NOT NULL default 'default'"
		),
		'preserveMetadataFields' => array
		(
			'inputType'               => 'checkboxWizard',
			'options_callback'        => array('tl_image_size', 'getMetadataFields'),
			'eval'                    => array('multiple'=>true, 'mandatory'=>true),
			'sql'                     => "blob NULL"
		),
		'skipIfDimensionsMatch' => array
		(
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'lazyLoading' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_image_size extends Backend
{
	/**
	 * List an image size
	 *
	 * @param array $row
	 *
	 * @return string
	 */
	public function listImageSize($row, $label)
	{
		if ($row['width'] || $row['height'])
		{
			$label .= ' <span class="label-info">' . $row['width'] . 'x' . $row['height'] . '</span>';
		}

		if ($row['zoom'])
		{
			$label .= ' <span class="label-info">(' . (int) $row['zoom'] . '%)</span>';
		}

		return $label;
	}

	/**
	 * Return the image format options
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getFormats(DataContainer|null $dc=null)
	{
		$formats = array();
		$missingSupport = array();

		if ($dc->value)
		{
			$formats = StringUtil::deserialize($dc->value, true);
		}

		$imageExtensions = System::getContainer()->getParameter('contao.image.valid_extensions');

		$supporedFormats = $this->getSupportedFormats();
		$supporedFormats['jpg'] = true;
		$supporedFormats['png'] = true;
		$supporedFormats['gif'] = true;

		foreach ($supporedFormats as $format => $isSupported)
		{
			if (!in_array($format, $imageExtensions))
			{
				continue;
			}

			if (!$isSupported)
			{
				$missingSupport[] = $format;

				continue;
			}

			foreach ($supporedFormats as $subFormat => $subFormatSupported)
			{
				if (
					!$subFormatSupported
					|| $subFormat === $format
					|| 'gif' === $subFormat
					|| (in_array($format, array('jpg', 'png', 'gif')) && in_array($subFormat, array('jpg', 'png', 'gif')))
				) {
					continue;
				}

				if ('jpg' === $format)
				{
					$formats[] = "jpg:jpg,$subFormat;jpeg:jpeg,$subFormat";
				}
				else
				{
					$formats[] = "$format:$format,$subFormat";
				}
			}
		}

		if ($missingSupport)
		{
			$GLOBALS['TL_DCA']['tl_image_size']['fields']['formats']['label'] = array
			(
				$GLOBALS['TL_LANG']['tl_image_size']['formats'][0],
				sprintf($GLOBALS['TL_LANG']['tl_image_size']['formatsNotSupported'], implode(', ', $missingSupport)),
			);
		}

		$options = array();
		$formats = array_values(array_unique($formats));

		foreach ($formats as $format)
		{
			list($first) = explode(';', $format);
			list($from, $to) = explode(':', $first);
			$chunks = array_values(array_diff(explode(',', $to), array($from)));

			$options[$format] = strtoupper($from) . ' → ' . strtoupper($chunks[0]);
		}

		asort($options);

		return $options;
	}

	/**
	 * Return the image metadata fields
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getMetadataFields(DataContainer|null $dc=null)
	{
		$options = array();

		foreach ((new ResizeOptions())->getPreserveCopyrightMetadata() as $key => $value)
		{
			$options[serialize(array($key => $value))] = strtoupper($key) . ' (' . implode(', ', iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($value)))) . ')';
		}

		return $options;
	}

	/**
	 * Check if WEBP, AVIF, HEIC or JXL is supported
	 *
	 * @return array
	 */
	private function getSupportedFormats()
	{
		$supported = array
		(
			'webp' => false,
			'avif' => false,
			'heic' => false,
			'jxl' => false,
		);

		$imagine = System::getContainer()->get('contao.image.imagine');

		foreach (array_keys($supported) as $format)
		{
			$supported[$format] = $imagine->getDriverInfo()->isFormatSupported($format);
		}

		return $supported;
	}
}
