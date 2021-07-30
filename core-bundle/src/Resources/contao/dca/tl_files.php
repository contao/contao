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
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\DC_Folder;
use Contao\File;
use Contao\FilesModel;
use Contao\Folder;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Patchwork\Utf8;
use Symfony\Component\Finder\Finder;

$GLOBALS['TL_DCA']['tl_files'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Folder',
		'enableVersioning'            => true,
		'databaseAssisted'            => true,
		'onload_callback' => array
		(
			array('tl_files', 'checkPermission'),
			array('tl_files', 'addBreadcrumb'),
			array('tl_files', 'adjustPalettes')
		),
		'oncreate_version_callback' => array
		(
			array('tl_files', 'createVersion')
		),
		'onrestore_version_callback' => array
		(
			array('tl_files', 'restoreVersion')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
				'uuid' => 'unique',
				'path' => 'index', // not unique (see #7725)
				'extension' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'panelLayout'             => 'search'
		),
		'global_operations' => array
		(
			'sync' => array
			(
				'href'                => 'act=sync',
				'class'               => 'header_sync',
				'button_callback'     => array('tl_files', 'syncFiles')
			),
			'toggleNodes' => array
			(
				'href'                => 'tg=all',
				'class'               => 'header_toggle',
				'showOnSelect'        => true
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
				'href'                => 'act=edit',
				'icon'                => 'edit.svg',
				'button_callback'     => array('tl_files', 'editFile')
			),
			'copy' => array
			(
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_files', 'copyFile')
			),
			'cut' => array
			(
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_files', 'cutFile')
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirmFile'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_files', 'deleteFile')
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg',
				'button_callback'     => array('tl_files', 'showFile')
			),
			'source' => array
			(
				'href'                => 'act=source',
				'icon'                => 'editor.svg',
				'button_callback'     => array('tl_files', 'editSource')
			),
			'upload' => array
			(
				'href'                => 'act=move&amp;mode=2',
				'icon'                => 'new.svg',
				'button_callback'     => array('tl_files', 'uploadFile')
			),
			'drag' => array
			(
				'icon'                => 'drag.svg',
				'attributes'          => 'class="drag-handle" aria-hidden="true"',
				'button_callback'     => array('tl_files', 'dragFile')
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => 'name,protected,syncExclude,importantPartX,importantPartY,importantPartWidth,importantPartHeight;meta'
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
			'sql'                     => "binary(16) NULL"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'uuid' => array
		(
			'sql'                     => "binary(16) NULL"
		),
		'type' => array
		(
			'sql'                     => "varchar(16) NOT NULL default ''"
		),
		'path' => array
		(
			'eval'                    => array('unique'=>true, 'versionize'=>false),
			'sql'                     => "varchar(1022) BINARY NOT NULL default ''",
		),
		'extension' => array
		(
			'sql'                     => "varchar(16) BINARY NOT NULL default ''"
		),
		'hash' => array
		(
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'found' => array
		(
			'sql'                     => "char(1) NOT NULL default '1'"
		),
		'name' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'versionize'=>false, 'maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50', 'addWizardClass'=>false),
			'wizard' => array
			(
				array('tl_files', 'addFileLocation')
			),
			'save_callback' => array
			(
				array('tl_files', 'checkFilename')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
		'protected' => array
		(
			'input_field_callback'    => array('tl_files', 'protectFolder'),
			'eval'                    => array('tl_class'=>'w50 clr')
		),
		'syncExclude' => array
		(
			'input_field_callback'    => array('tl_files', 'excludeFolder'),
			'eval'                    => array('tl_class'=>'w50')
		),
		'importantPartX' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "DOUBLE unsigned NOT NULL default 0"
		),
		'importantPartY' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "DOUBLE unsigned NOT NULL default 0"
		),
		'importantPartWidth' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "DOUBLE unsigned NOT NULL default 0"
		),
		'importantPartHeight' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "DOUBLE unsigned NOT NULL default 0"
		),
		'meta' => array
		(
			'inputType'               => 'metaWizard',
			'eval'                    => array
			(
				'allowHtml'           => true,
				'multiple'            => true,
				'metaFields'          => array
				(
					'title'           => 'maxlength="255"',
					'alt'             => 'maxlength="255"',
					'link'            => array('attributes'=>'maxlength="255"', 'dcaPicker'=>true),
					'caption'         => array('type'=>'textarea')
				)
			),
			'sql'                     => "blob NULL"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_files extends Backend
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
	 * Check permissions to edit the file system
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Permissions
		if (!is_array($this->User->fop))
		{
			$this->User->fop = array();
		}

		$canUpload = $this->User->hasAccess('f1', 'fop');
		$canEdit = $this->User->hasAccess('f2', 'fop');
		$canDeleteOne = $this->User->hasAccess('f3', 'fop');
		$canDeleteRecursive = $this->User->hasAccess('f4', 'fop');

		// Set the filemounts
		$GLOBALS['TL_DCA']['tl_files']['list']['sorting']['root'] = $this->User->filemounts;

		// Disable the upload button if uploads are not allowed
		if (!$canUpload)
		{
			$GLOBALS['TL_DCA']['tl_files']['config']['closed'] = true;
		}

		// Disable the edit_all button
		if (!$canEdit)
		{
			$GLOBALS['TL_DCA']['tl_files']['config']['notEditable'] = true;
		}

		// Disable the delete_all button
		if (!$canDeleteOne && !$canDeleteRecursive)
		{
			$GLOBALS['TL_DCA']['tl_files']['config']['notDeletable'] = true;
		}

		$container = System::getContainer();
		$projectDir = $container->getParameter('kernel.project_dir');
		$objSession = $container->get('session');

		$session = $objSession->all();

		// Set allowed page IDs (edit multiple)
		if (is_array($session['CURRENT']['IDS']))
		{
			if (Input::get('act') == 'editAll')
			{
				if (!$canEdit)
				{
					$session['CURRENT']['IDS'] = array();
				}
			}

			// Check delete permissions
			else
			{
				$folders = array();
				$delete_all = array();

				foreach ($session['CURRENT']['IDS'] as $id)
				{
					if (is_dir($projectDir . '/' . $id))
					{
						$folders[] = $id;

						if ($canDeleteRecursive || ($canDeleteOne && count(Folder::scan($projectDir . '/' . $id)) < 1))
						{
							$delete_all[] = $id;
						}
					}
					elseif (($canDeleteOne || $canDeleteRecursive) && !in_array(dirname($id), $folders))
					{
						$delete_all[] = $id;
					}
				}

				$session['CURRENT']['IDS'] = $delete_all;
			}
		}

		// Set allowed clipboard IDs
		if (!$canEdit && isset($session['CLIPBOARD']['tl_files']))
		{
			$session['CLIPBOARD']['tl_files'] = array();
		}

		// Overwrite session
		$objSession->replace($session);

		// Check current action
		if (Input::get('act') && Input::get('act') != 'paste')
		{
			switch (Input::get('act'))
			{
				case 'move':
					if (!$canUpload)
					{
						throw new AccessDeniedException('No permission to upload files.');
					}
					break;

				case 'edit':
				case 'create':
				case 'copy':
				case 'copyAll':
				case 'cut':
				case 'cutAll':
					if (!$canEdit)
					{
						throw new AccessDeniedException('No permission to create, edit, copy or move files.');
					}
					break;

				case 'delete':
					$strFile = Input::get('id', true);

					if (is_dir($projectDir . '/' . $strFile))
					{
						$finder = Finder::create()->in($projectDir . '/' . $strFile);

						if (!$canDeleteRecursive && $finder->hasResults())
						{
							throw new AccessDeniedException('No permission to delete folder "' . $strFile . '" recursively.');
						}

						if (!$canDeleteOne)
						{
							throw new AccessDeniedException('No permission to delete folder "' . $strFile . '".');
						}
					}
					elseif (!$canDeleteOne)
					{
						throw new AccessDeniedException('No permission to delete file "' . $strFile . '".');
					}
					break;

				case 'source':
					if (!$this->User->hasAccess('f5', 'fop'))
					{
						throw new AccessDeniedException('Not enough permissions to edit the source of file "' . Input::get('id', true) . '".');
					}
					break;

				case 'sync':
					if (!$this->User->hasAccess('f6', 'fop'))
					{
						throw new AccessDeniedException('No permission to synchronize the file system with the database.');
					}
					break;

				default:
					if (empty($this->User->fop))
					{
						throw new AccessDeniedException('No permission to manipulate files.');
					}
					break;
			}
		}
	}

	/**
	 * Add the breadcrumb menu
	 */
	public function addBreadcrumb()
	{
		Backend::addFilesBreadcrumb();
	}

	/**
	 * Adjust the palettes
	 *
	 * @param DataContainer $dc
	 */
	public function adjustPalettes(DataContainer $dc)
	{
		if (!$dc->id)
		{
			return;
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$blnIsFolder = is_dir($projectDir . '/' . $dc->id);

		// Remove the metadata when editing folders
		if ($blnIsFolder)
		{
			PaletteManipulator::create()
				->removeField('meta')
				->applyToPalette('default', $dc->table)
			;
		}

		// Only show the important part fields for images
		if ($blnIsFolder || !in_array(strtolower(substr($dc->id, strrpos($dc->id, '.') + 1)), StringUtil::trimsplit(',', strtolower(Config::get('validImageTypes')))))
		{
			PaletteManipulator::create()
				->removeField(array('importantPartX', 'importantPartY', 'importantPartWidth', 'importantPartHeight'))
				->applyToPalette('default', $dc->table)
			;
		}
	}

	/**
	 * Store the content if it is an editable file
	 *
	 * @param string  $table
	 * @param integer $pid
	 * @param integer $version
	 * @param array   $data
	 */
	public function createVersion($table, $pid, $version, $data)
	{
		$model = FilesModel::findByPk($pid);

		if ($model === null || !in_array($model->extension, StringUtil::trimsplit(',', strtolower(Config::get('editableFiles')))))
		{
			return;
		}

		$file = new File($model->path);

		if ($file->extension == 'svgz')
		{
			$data['content'] = gzdecode($file->getContent());
		}
		else
		{
			$data['content'] = $file->getContent();
		}

		$this->Database->prepare("UPDATE tl_version SET data=? WHERE pid=? AND version=? AND fromTable=?")
					   ->execute(serialize($data), $pid, $version, $table);
	}

	/**
	 * Restore the content if it is an editable file
	 *
	 * @param string  $table
	 * @param integer $pid
	 * @param integer $version
	 * @param array   $data
	 */
	public function restoreVersion($table, $pid, $version, $data)
	{
		$model = FilesModel::findByPk($pid);

		if ($model === null || !in_array($model->extension, StringUtil::trimsplit(',', strtolower(Config::get('editableFiles')))))
		{
			return;
		}

		// Refetch the data, because not existing field have been unset
		$objData = $this->Database->prepare("SELECT data FROM tl_version WHERE fromTable=? AND pid=? AND version=?")
								  ->limit(1)
								  ->execute($table, $pid, $version);

		if ($objData->numRows < 1)
		{
			return;
		}

		$arrData = StringUtil::deserialize($objData->data);

		if (!is_array($arrData) || !isset($arrData['content']))
		{
			return;
		}

		$file = new File($model->path);

		if ($file->extension == 'svgz')
		{
			$file->write(gzencode($arrData['content']));
		}
		else
		{
			$file->write($arrData['content']);
		}

		$file->close();
	}

	/**
	 * Add the file location instead of the help text (see #6503)
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 */
	public function addFileLocation(DataContainer $dc)
	{
		// Unset the default help text
		unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][1]);

		return '<p class="tl_help tl_tip">' . sprintf($GLOBALS['TL_LANG']['tl_files']['fileLocation'], $dc->id) . '</p>';
	}

	/**
	 * Check a file name and romanize it
	 *
	 * @param string                  $varValue
	 * @param DataContainer|DC_Folder $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function checkFilename($varValue, DataContainer $dc)
	{
		$varValue = str_replace('"', '', $varValue);

		if (strpos($varValue, '/') !== false || preg_match('/\.$/', $varValue))
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['invalidName']);
		}

		// Check the length without the file extension
		if ($dc->activeRecord && $varValue)
		{
			$intMaxlength = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['maxlength'] ?? null;

			if ($intMaxlength)
			{
				if ($dc->activeRecord->type == 'file')
				{
					$intMaxlength -= (strlen($dc->activeRecord->extension) + 1);
				}

				if (Utf8::strlen($varValue) > $intMaxlength)
				{
					throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['maxlength'], $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0], $intMaxlength));
				}
			}
		}

		return $varValue;
	}

	/**
	 * Return the sync files button
	 *
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $class
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function syncFiles($href, $label, $title, $class, $attributes)
	{
		return $this->User->hasAccess('f6', 'fop') ? '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '" class="' . $class . '"' . $attributes . '>' . $label . '</a> ' : '';
	}

	/**
	 * Return the edit file button
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
	public function editFile($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->hasAccess('f2', 'fop') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the copy file button
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
	public function copyFile($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->hasAccess('f2', 'fop') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the cut file button
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
	public function cutFile($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->hasAccess('f2', 'fop') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the drag file button
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
	public function dragFile($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->hasAccess('f2', 'fop') ? '<button type="button" title="' . StringUtil::specialchars($title) . '" ' . $attributes . '>' . Image::getHtml($icon, $label) . '</button> ' : ' ';
	}

	/**
	 * Return the upload file button
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
	public function uploadFile($row, $href, $label, $title, $icon, $attributes)
	{
		if (($row['type'] ?? null) == 'folder' && !($GLOBALS['TL_DCA']['tl_files']['config']['closed'] ?? null) && !($GLOBALS['TL_DCA']['tl_files']['config']['notCreatable'] ?? null) && Input::get('act') != 'select')
		{
			return '<a href="' . $this->addToUrl($href . '&amp;pid=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '" ' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
		}

		return ' ';
	}

	/**
	 * Return the delete file button
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
	public function deleteFile($row, $href, $label, $title, $icon, $attributes)
	{
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$path = $projectDir . '/' . urldecode($row['id']);

		if (!is_dir($path))
		{
			return ($this->User->hasAccess('f3', 'fop') || $this->User->hasAccess('f4', 'fop')) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
		}

		$finder = Finder::create()->in($path);

		if ($finder->hasResults())
		{
			return $this->User->hasAccess('f4', 'fop') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
		}

		return $this->User->hasAccess('f3', 'fop') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the edit file source button
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
	public function editSource($row, $href, $label, $title, $icon, $attributes)
	{
		if (!$this->User->hasAccess('f5', 'fop'))
		{
			return '';
		}

		$strDecoded = rawurldecode($row['id']);
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		if (is_dir($projectDir . '/' . $strDecoded))
		{
			return '';
		}

		$objFile = new File($strDecoded);

		if (!in_array($objFile->extension, StringUtil::trimsplit(',', strtolower(Config::get('editableFiles')))))
		{
			return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
		}

		return '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
	}

	/**
	 * Return the show file button
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
	public function showFile($row, $href, $label, $title, $icon, $attributes)
	{
		if (Input::get('popup'))
		{
			return '';
		}

		return '<a href="contao/popup.php?src=' . base64_encode($row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . ' onclick="Backend.openModalIframe({\'title\':\'' . str_replace("'", "\\'", StringUtil::specialchars($row['fileNameEncoded'])) . '\',\'url\':this.href});return false">' . Image::getHtml($icon, $label) . '</a> ';
	}

	/**
	 * Return a checkbox to protect a folder
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws RuntimeException
	 */
	public function protectFolder(DataContainer $dc)
	{
		$strPath = $dc->id;
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		// Only show for folders (see #5660)
		if (!is_dir($projectDir . '/' . $strPath))
		{
			return '';
		}

		$objFolder = new Folder($strPath);

		// Check if the folder or a parent folder is public
		$blnUnprotected = $objFolder->isUnprotected();

		// Disable the checkbox if a parent folder is public (see #712)
		$blnDisable = $blnUnprotected && !file_exists($projectDir . '/' . $strPath . '/.public');

		// Protect or unprotect the folder
		if (!$blnDisable && Input::post('FORM_SUBMIT') == 'tl_files')
		{
			if (Input::post($dc->inputName))
			{
				if (!$blnUnprotected)
				{
					$blnUnprotected = true;
					$objFolder->unprotect();

					$this->import(Automator::class, 'Automator');
					$this->Automator->generateSymlinks();

					$this->log('Folder "' . $strPath . '" has been published', __METHOD__, TL_FILES);
				}
			}
			elseif ($blnUnprotected)
			{
				$blnUnprotected = false;
				$objFolder->protect();

				$this->import(Automator::class, 'Automator');
				$this->Automator->generateSymlinks();

				$this->log('Folder "' . $strPath . '" has been protected', __METHOD__, TL_FILES);
			}
		}

		$class = ($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['tl_class'] ?? '') . ' cbx';

		if (in_array(Input::get('act'), array('editAll', 'overrideAll')))
		{
			$class = str_replace(array('w50', 'clr', 'wizard', 'long', 'm12', 'cbx'), '', $class);
		}

		$class = trim('widget ' . $class);

		return '
<div class="' . $class . '">
  <div id="ctrl_' . $dc->field . '" class="tl_checkbox_single_container">
    <input type="hidden" name="' . $dc->inputName . '" value=""><input type="checkbox" name="' . $dc->inputName . '" id="opt_' . $dc->inputName . '_0" class="tl_checkbox" value="1"' . (($blnUnprotected || basename($strPath) == '__new__') ? ' checked="checked"' : '') . ' onfocus="Backend.getScrollOffset()"' . ($blnDisable ? ' disabled' : '') . '> <label for="opt_' . $dc->inputName . '_0">' . $GLOBALS['TL_LANG']['tl_files']['protected'][0] . '</label>
  </div>' . (Config::get('showHelp') ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_files']['protected'][1] . '</p>' : '') . '
</div>';
	}

	/**
	 * Return a checkbox to exclude a folder from synchronization
	 *
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws RuntimeException
	 */
	public function excludeFolder(DataContainer $dc)
	{
		$strPath = $dc->id;
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		// Check if the folder has been renamed (see #6432, #934)
		if (Input::post('name'))
		{
			if (Validator::isInsecurePath(Input::post('name')))
			{
				throw new RuntimeException('Invalid file or folder name ' . Input::post('name'));
			}

			$count = 0;
			$strName = basename($strPath);

			if ($count > 0 && ($strNewPath = str_replace($strName, Input::post('name'), $strPath, $count)) && is_dir($projectDir . '/' . $strNewPath))
			{
				$strPath = $strNewPath;
			}
		}

		// Only show for folders (see #5660)
		if (!is_dir($projectDir . '/' . $strPath))
		{
			return '';
		}

		$objFolder = new Folder($strPath);

		// Check if the folder or a parent folder is unsynchronized
		$blnUnsynchronized = $objFolder->isUnsynchronized();

		// Disable the checkbox if a parent folder is unsynchronized
		$blnDisable = $blnUnsynchronized && !file_exists($projectDir . '/' . $strPath . '/.nosync');

		// Synchronize or unsynchronize the folder
		if (!$blnDisable && Input::post('FORM_SUBMIT') == 'tl_files')
		{
			if (Input::post($dc->inputName))
			{
				if (!$blnUnsynchronized)
				{
					$blnUnsynchronized = true;
					$objFolder->unsynchronize();

					$this->log('Synchronization of folder "' . $strPath . '" has been disabled', __METHOD__, TL_FILES);
				}
			}
			elseif ($blnUnsynchronized)
			{
				$blnUnsynchronized = false;
				$objFolder->synchronize();

				$this->log('Synchronization of folder "' . $strPath . '" has been enabled', __METHOD__, TL_FILES);
			}
		}

		$class = ($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['tl_class'] ?? '') . ' cbx';

		if (in_array(Input::get('act'), array('editAll', 'overrideAll')))
		{
			$class = str_replace(array('w50', 'clr', 'wizard', 'long', 'm12', 'cbx'), '', $class);
		}

		$class = trim('widget ' . $class);

		return '
<div class="' . $class . '">
  <div id="ctrl_' . $dc->field . '" class="tl_checkbox_single_container">
    <input type="hidden" name="' . $dc->inputName . '" value=""><input type="checkbox" name="' . $dc->inputName . '" id="opt_' . $dc->inputName . '_0" class="tl_checkbox" value="1"' . ($blnUnsynchronized ? ' checked="checked"' : '') . ' onfocus="Backend.getScrollOffset()"' . ($blnDisable ? ' disabled' : '') . '> <label for="opt_' . $dc->inputName . '_0">' . $GLOBALS['TL_LANG']['tl_files']['syncExclude'][0] . '</label>
  </div>' . (Config::get('showHelp') ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_files']['syncExclude'][1] . '</p>' : '') . '
</div>';
	}
}
