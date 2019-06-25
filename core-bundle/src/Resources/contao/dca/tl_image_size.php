<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_image_size'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
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
			'mode'                    => 4,
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
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
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
		'default'                     => '{title_legend},name,width,height,resizeMode,zoom;{expert_legend},cssClass,densities,sizes'
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
			'flag'                    => 1,
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
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_image_size extends Contao\Backend
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

		if (!$this->User->hasAccess('image_sizes', 'themes'))
		{
			throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access the image sizes module.');
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
		if (\func_num_args() == 4)
		{
			$insertId = func_get_arg(1);
		}

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set the image sizes
		if (empty($this->User->imageSizes) || !\is_array($this->User->imageSizes))
		{
			$imageSizes = array();
		}
		else
		{
			$imageSizes = $this->User->imageSizes;
		}

		// The image size is enabled already
		if (\in_array($insertId, $imageSizes))
		{
			return;
		}

		/** @var Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface $objSessionBag */
		$objSessionBag = Contao\System::getContainer()->get('session')->getBag('contao_backend');

		$arrNew = $objSessionBag->get('new_records');

		if (\is_array($arrNew['tl_image_size']) && \in_array($insertId, $arrNew['tl_image_size']))
		{
			// Add the permissions on group level
			if ($this->User->inherit != 'custom')
			{
				$objGroup = $this->Database->execute("SELECT id, themes, imageSizes FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $this->User->groups)) . ")");

				while ($objGroup->next())
				{
					$arrThemes = Contao\StringUtil::deserialize($objGroup->themes);

					if (\is_array($arrThemes) && \in_array('image_sizes', $arrThemes))
					{
						$arrImageSizes = Contao\StringUtil::deserialize($objGroup->imageSizes, true);
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

				$arrThemes = Contao\StringUtil::deserialize($objUser->themes);

				if (\is_array($arrThemes) && \in_array('image_sizes', $arrThemes))
				{
					$arrImageSizes = Contao\StringUtil::deserialize($objUser->imageSizes, true);
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
		return $this->User->canEditFieldsOf('tl_image_size') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.Contao\Image::getHtml($icon, $label).'</a> ' : Contao\Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
	}
}
