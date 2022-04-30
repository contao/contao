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
use Contao\StringUtil;
use Contao\System;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Imagick\Imagine as ImagickImagine;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

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
		'onload_callback' => array
		(
			array('tl_image_size', 'checkPermission')
		),
		'oncreate_callback' => array
		(
			array('tl_image_size', 'adjustPermissions')
		),
		'oncopy_callback' => array
		(
			array('tl_image_size', 'adjustPermissions')
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
			'fields'                  => array('name'),
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('name', 'author', 'tstamp'),
			'child_record_callback'   => array('tl_image_size', 'listImageSize')
		),
		'global_operations' => array
		(
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
				'href'                => 'table=tl_image_size_item',
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'href'                => 'table=tl_image_size&amp;act=edit',
				'icon'                => 'header.svg',
				'button_callback'     => array('tl_image_size', 'editHeader')
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
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},name,width,height,resizeMode,zoom;{source_legend},densities,sizes;{loading_legend},lazyLoading;{expert_legend:hide},formats,cssClass,skipIfDimensionsMatch'
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
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NULL"
		),
		'cssClass' => array
		(
			'inputType'               => 'text',
			'exclude'                 => true,
			'search'                  => true,
			'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'densities' => array
		(
			'inputType'               => 'text',
			'explanation'             => 'imageSizeDensities',
			'exclude'                 => true,
			'eval'                    => array('helpwizard'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'sizes' => array
		(
			'inputType'               => 'text',
			'explanation'             => 'imageSizeDensities',
			'exclude'                 => true,
			'eval'                    => array('helpwizard'=>true, 'maxlength'=>255, 'tl_class'=>'clr', 'decodeEntities'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'width' => array
		(
			'inputType'               => 'text',
			'exclude'                 => true,
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'clr w50'),
			'sql'                     => "int(10) NULL"
		),
		'height' => array
		(
			'inputType'               => 'text',
			'exclude'                 => true,
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) NULL"
		),
		'resizeMode' => array
		(
			'inputType'               => 'select',
			'options'                 => array('proportional', 'box', 'crop'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_image_size'],
			'exclude'                 => true,
			'eval'                    => array('helpwizard'=>true, 'tl_class'=>'clr w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'zoom' => array
		(
			'inputType'               => 'text',
			'exclude'                 => true,
			'eval'                    => array('rgxp'=>'prcnt', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) NULL"
		),
		'formats' => array
		(
			'inputType'               => 'checkboxWizard',
			'options_callback'        => array('tl_image_size', 'getFormats'),
			'exclude'                 => true,
			'eval'                    => array('multiple'=>true),
			'sql'                     => "varchar(1024) NOT NULL default ''"
		),
		'skipIfDimensionsMatch' => array
		(
			'inputType'               => 'checkbox',
			'exclude'                 => true,
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'lazyLoading' => array
		(
			'inputType'               => 'checkbox',
			'exclude'                 => true,
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_image_size extends Backend
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
	 * Add the new image size to the permissions
	 *
	 * @param $insertId
	 */
	public function adjustPermissions($insertId)
	{
		// The oncreate_callback passes $insertId as second argument
		if (func_num_args() == 4)
		{
			$insertId = func_get_arg(1);
		}

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set the image sizes
		if (empty($this->User->imageSizes) || !is_array($this->User->imageSizes))
		{
			$imageSizes = array();
		}
		else
		{
			$imageSizes = $this->User->imageSizes;
		}

		// The image size is enabled already
		if (in_array($insertId, $imageSizes))
		{
			return;
		}

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');
		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_image_size']) && in_array($insertId, $arrNew['tl_image_size']))
		{
			// Add the permissions on group level
			if ($this->User->inherit != 'custom')
			{
				$objGroup = $this->Database->execute("SELECT id, themes, imageSizes FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $this->User->groups)) . ")");

				while ($objGroup->next())
				{
					$arrThemes = StringUtil::deserialize($objGroup->themes);

					if (is_array($arrThemes) && in_array('image_sizes', $arrThemes))
					{
						$arrImageSizes = StringUtil::deserialize($objGroup->imageSizes, true);
						$arrImageSizes[] = $insertId;

						$this->Database->prepare("UPDATE tl_user_group SET imageSizes=? WHERE id=?")
									   ->execute(serialize($arrImageSizes), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($this->User->inherit != 'group')
			{
				$objUser = $this->Database->prepare("SELECT themes, imageSizes FROM tl_user WHERE id=?")
										   ->limit(1)
										   ->execute($this->User->id);

				$arrThemes = StringUtil::deserialize($objUser->themes);

				if (is_array($arrThemes) && in_array('image_sizes', $arrThemes))
				{
					$arrImageSizes = StringUtil::deserialize($objUser->imageSizes, true);
					$arrImageSizes[] = $insertId;

					$this->Database->prepare("UPDATE tl_user SET imageSizes=? WHERE id=?")
								   ->execute(serialize($arrImageSizes), $this->User->id);
				}
			}

			// Add the new element to the user object
			$imageSizes[] = $insertId;
			$this->User->imageSizes = $imageSizes;
		}
	}

	/**
	 * List an image size
	 *
	 * @param array $row
	 *
	 * @return string
	 */
	public function listImageSize($row)
	{
		$html = '<div class="tl_content_left">';
		$html .= $row['name'];

		if ($row['width'] || $row['height'])
		{
			$html .= ' <span style="color:#999;padding-left:3px">' . $row['width'] . 'x' . $row['height'] . '</span>';
		}

		if ($row['zoom'])
		{
			$html .= ' <span style="color:#999;padding-left:3px">(' . (int) $row['zoom'] . '%)</span>';
		}

		$html .= "</div>\n";

		return $html;
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
		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_image_size') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the image format options
	 *
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function getFormats(DataContainer $dc=null)
	{
		$formats = array();
		$missingSupport = array();

		if ($dc->value)
		{
			$formats = StringUtil::deserialize($dc->value, true);
		}

		foreach ($this->getSupportedFormats() as $format => $isSupported)
		{
			if (!in_array($format, System::getContainer()->getParameter('contao.image.valid_extensions')))
			{
				continue;
			}

			if (!$isSupported)
			{
				$missingSupport[] = $format;

				continue;
			}

			$formats[] = "png:$format,png";
			$formats[] = "jpg:$format,jpg;jpeg:$format,jpeg";
			$formats[] = "gif:$format,gif";
			$formats[] = "$format:$format,png";
			$formats[] = "$format:$format,jpg";
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

		if ($imagine instanceof ImagickImagine)
		{
			foreach (array_keys($supported) as $format)
			{
				$supported[$format] = in_array(strtoupper($format), Imagick::queryFormats(strtoupper($format)), true);
			}
		}

		if ($imagine instanceof GmagickImagine)
		{
			foreach (array_keys($supported) as $format)
			{
				$supported[$format] = in_array(strtoupper($format), (new Gmagick())->queryformats(strtoupper($format)), true);
			}
		}

		if ($imagine instanceof GdImagine)
		{
			foreach (array_keys($supported) as $format)
			{
				$supported[$format] = function_exists('image' . $format);
			}
		}

		return $supported;
	}
}
