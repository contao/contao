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
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\File\TextTrackType;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Folder;
use Contao\File;
use Contao\FilesModel;
use Contao\Folder;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

$GLOBALS['TL_DCA']['tl_files'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Folder::class,
		'enableVersioning'            => true,
		'databaseAssisted'            => true,
		'uploadPath'                  => System::getContainer()->getParameter('contao.upload_path'),
		'editableFileTypes'           => System::getContainer()->getParameter('contao.editable_files'),
		'onload_callback' => array
		(
			array('tl_files', 'checkPermission'),
			array('tl_files', 'addBreadcrumb'),
		),
		'oncreate_version_callback' => array
		(
			array('tl_files', 'createVersion')
		),
		'onrestore_version_callback' => array
		(
			array('tl_files', 'restoreVersion')
		),
		'onpalette_callback' => array
		(
			array('tl_files', 'adjustPalettes')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
				'tstamp' => 'index',
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
			'panelLayout'             => 'search',
			'showRootTrails'          => true
		),
		'global_operations' => array
		(
			'sync' => array
			(
				'href'                => 'act=sync',
				'class'               => 'header_sync',
				'primary'             => true,
				'button_callback'     => array('tl_files', 'syncFiles')
			),
		),
		'operations' => array
		(
			'edit' => array
			(
				'href'                => 'act=edit',
				'prefetch'            => true,
				'icon'                => 'edit.svg',
				'attributes'          => 'data-contao--deeplink-target="primary"',
				'primary'             => true,
				'button_callback'     => array('tl_files', 'editFile')
			),
			'source' => array
			(
				'href'                => 'act=source',
				'prefetch'            => true,
				'icon'                => 'editor.svg',
				'primary'             => true,
				'button_callback'     => array('tl_files', 'editSource')
			),
			'copy' => array
			(
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.svg',
				'attributes'          => 'data-action="contao--scroll-offset#store"',
				'button_callback'     => array('tl_files', 'canRenameFile')
			),
			'cut' => array
			(
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'data-action="contao--scroll-offset#store"',
				'button_callback'     => array('tl_files', 'canRenameFile')
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'data-action="contao--scroll-offset#store" onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirmFile'] ?? null) . '\'))return false"',
				'button_callback'     => array('tl_files', 'deleteFile')
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg',
				'button_callback'     => array('tl_files', 'showFile')
			),
			'-',
			'upload' => array
			(
				'href'                => 'act=move&amp;mode=2',
				'icon'                => 'new.svg',
				'primary'             => true,
				'button_callback'     => array('tl_files', 'uploadFile')
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => 'preview,name,protected,syncExclude,importantPartX,importantPartY,importantPartWidth,importantPartHeight;meta'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'autoincrement'=>true)
		),
		'pid' => array
		(
			'sql'                     => array('type'=>'binary', 'length'=>16, 'fixed'=>true, 'notnull'=>false)
		),
		'tstamp' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'uuid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['fileUuid'],
			'sql'                     => array('type'=>'binary', 'length'=>16, 'fixed'=>true, 'notnull'=>false)
		),
		'type' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>16, 'default'=>'')
		),
		'path' => array
		(
			'eval'                    => array('unique'=>true, 'versionize'=>false),
			'sql'                     => array('type'=>'string', 'length'=>1022, 'default'=>'', 'customSchemaOptions'=>array('collation'=>'utf8mb4_bin')),
		),
		'extension' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>16, 'default'=>'', 'customSchemaOptions'=>array('collation'=>'utf8mb4_bin'))
		),
		'hash' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'lastModified' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'notnull'=>false)
		),
		'found' => array
		(
			'sql'                     => array('type'=>'boolean', 'default'=>true)
		),
		'preview' => array
		(
			// input_field_callback from FileImagePreviewListener
			'exclude' => false,
		),
		'name' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'versionize'=>false, 'maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'load_callback' => array
			(
				array('tl_files', 'addFileLocation')
			),
			'save_callback' => array
			(
				array('tl_files', 'checkFilename')
			),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'', 'customSchemaOptions'=>array('collation'=>'utf8mb4_bin'))
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
			'sql'                     => array('type'=>'float', 'unsigned'=>true, 'default'=>'0')
		),
		'importantPartY' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'float', 'unsigned'=>true, 'default'=>'0')
		),
		'importantPartWidth' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => array('type'=>'float', 'unsigned'=>true, 'default'=>'0')
		),
		'importantPartHeight' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'float', 'unsigned'=>true, 'default'=>'0')
		),
		'textTrackLanguage' => array
		(
			'filter'                  => true,
			'inputType'               => 'select',
			'eval'                    => array('mandatory' => true, 'includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50 clr'),
			'options_callback'        => static fn () => System::getContainer()->get('contao.intl.locales')->getLocales(),
			'sql'                     => array('type'=>'string', 'length'=>64, 'default'=>'')
		),
		'textTrackType' => array
		(
			'inputType'               => 'select',
			'reference'               => &$GLOBALS['TL_LANG']['tl_files'],
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
			'options_callback'        => static fn () => array_map(static fn ($case) => $case->name, TextTrackType::cases()),
			'sql'                     => array('type'=>'string', 'length'=>12, 'notnull'=>false)
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
					'link'            => array('attributes'=>'maxlength="2048"', 'dcaPicker'=>true),
					'caption'         => array('type'=>'textarea', 'basicEntities'=>true),
					'license'         => array(
						'attributes'  => 'maxlength="255"',
						'dcaPicker'   => true,
						'rgxp'        => '#(^$|^{{link_url::.+$|^https?://.+$)#',
						'rgxpErrMsg'  => &$GLOBALS['TL_LANG']['tl_files']['licenseRgxpError']
					)
				)
			),
			'sql'                     => array('type'=>'blob', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull'=>false)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_files extends Backend
{
	/**
	 * Check permissions to edit the file system
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		$user = BackendUser::getInstance();

		if ($user->isAdmin)
		{
			return;
		}

		// Permissions
		if (!is_array($user->fop))
		{
			$user->fop = array();
		}

		$security = System::getContainer()->get('security.helper');
		$canUpload = $security->isGranted(ContaoCorePermissions::USER_CAN_UPLOAD_FILES);
		$canEdit = $security->isGranted(ContaoCorePermissions::USER_CAN_RENAME_FILE);
		$canDeleteOne = $security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_FILE);
		$canDeleteRecursive = $security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_RECURSIVELY);

		// Set the file mounts
		$GLOBALS['TL_DCA']['tl_files']['list']['sorting']['root'] = $user->filemounts;

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
		$objSession = $container->get('request_stack')->getSession();

		$session = $objSession->all();

		// Set allowed page IDs (edit multiple)
		if (is_array($session['CURRENT']['IDS'] ?? null))
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
					if (!$security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FILE))
					{
						throw new AccessDeniedException('Not enough permissions to edit the source of file "' . Input::get('id', true) . '".');
					}
					break;

				case 'sync':
					if (!$security->isGranted(ContaoCorePermissions::USER_CAN_SYNC_DBAFS))
					{
						throw new AccessDeniedException('No permission to synchronize the file system with the database.');
					}
					break;

				default:
					if (empty($user->fop))
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
	public function adjustPalettes(string $strPalette, DataContainer $dc)
	{
		if (!$dc->id)
		{
			return $strPalette;
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$blnIsFolder = is_dir($projectDir . '/' . $dc->id);

		// Remove the metadata when editing folders
		if ($blnIsFolder)
		{
			$strPalette = PaletteManipulator::create()
				->removeField('meta')
				->applyToString($strPalette)
			;
		}

		// Only show the important part fields for images
		if ($blnIsFolder || !in_array(Path::getExtension($dc->id, true), System::getContainer()->getParameter('contao.image.valid_extensions')))
		{
			$strPalette = PaletteManipulator::create()
				->removeField(array('importantPartX', 'importantPartY', 'importantPartWidth', 'importantPartHeight'))
				->applyToString($strPalette)
			;
		}

		return $strPalette;
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
		$model = FilesModel::findById($pid);

		if ($model === null || !in_array($model->extension, StringUtil::trimsplit(',', strtolower($GLOBALS['TL_DCA'][$table]['config']['editableFileTypes'] ?? System::getContainer()->getParameter('contao.editable_files')))))
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

		Database::getInstance()
			->prepare("UPDATE tl_version SET data=? WHERE pid=? AND version=? AND fromTable=?")
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
		$model = FilesModel::findById($pid);

		if ($model === null || !in_array($model->extension, StringUtil::trimsplit(',', strtolower($GLOBALS['TL_DCA'][$table]['config']['editableFileTypes'] ?? System::getContainer()->getParameter('contao.editable_files')))))
		{
			return;
		}

		// Refetch the data, because not existing field have been unset
		$objData = Database::getInstance()
			->prepare("SELECT data FROM tl_version WHERE fromTable=? AND pid=? AND version=?")
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
	 * @param mixed         $value
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 */
	public function addFileLocation($value, DataContainer $dc)
	{
		$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][1] = sprintf($GLOBALS['TL_LANG']['tl_files']['fileLocation'], $dc->id);

		return $value;
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
		$chunks = array_filter(explode('/', $varValue), 'strlen');

		if (count($chunks) < 1)
		{
			return '';
		}

		// Only allow slashes when creating new folders
		if ($dc->value != '__new__' && count($chunks) > 1)
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['invalidName']);
		}

		foreach ($chunks as $chunk)
		{
			if (preg_match('/\.$/', $chunk))
			{
				throw new Exception($GLOBALS['TL_LANG']['ERR']['invalidName']);
			}
		}

		// Check the length without the file extension
		if ($dc->activeRecord)
		{
			$intMaxlength = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['maxlength'] ?? null;

			if ($intMaxlength)
			{
				if ($dc->activeRecord->type == 'file')
				{
					$intMaxlength -= (strlen($dc->activeRecord->extension) + 1);
				}

				foreach ($chunks as $chunk)
				{
					if (mb_strlen($chunk) > $intMaxlength)
					{
						throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['maxlength'], $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0], $intMaxlength));
					}
				}
			}
		}

		return implode('/', $chunks);
	}

	/**
	 * Adjust the sync files button
	 */
	public function syncFiles(DataContainerOperation $operation)
	{
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_SYNC_DBAFS))
		{
			$operation->hide();
		}
	}

	/**
	 * Adjust the edit file button
	 */
	public function editFile(DataContainerOperation $operation)
	{
		$security = System::getContainer()->get('security.helper');
		$subject = new UpdateAction('tl_files', $operation->getRecord());

		if (!$security->isGranted(ContaoCorePermissions::DC_PREFIX . 'tl_files', $subject) || !$security->isGranted(ContaoCorePermissions::USER_CAN_RENAME_FILE))
		{
			$operation->disable();
		}
	}

	/**
	 * Adjust the copy and cut file buttons
	 */
	public function canRenameFile(DataContainerOperation $operation)
	{
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_RENAME_FILE))
		{
			$operation->disable();
		}
	}

	/**
	 * Adjust the upload file button
	 */
	public function uploadFile(DataContainerOperation $operation)
	{
		$row = $operation->getRecord();
		$table = $operation->getDataContainer()->table;

		if (Input::get('act') === 'select' || ($row['type'] ?? null) !== 'folder' || ($GLOBALS['TL_DCA']['tl_files']['config']['closed'] ?? null) || ($GLOBALS['TL_DCA']['tl_files']['config']['notMovable'] ?? null) || !System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::DC_PREFIX . $table, new CreateAction($table, array('pid' => $row['id'], 'type' => 'file'))))
		{
			$operation->hide();
		}
		else
		{
			$operation->setUrl(Backend::addToUrl($operation['href'] . '&amp;pid=' . $row['id']));
		}
	}

	/**
	 * Adjust the delete file button
	 */
	public function deleteFile(DataContainerOperation $operation)
	{
		$security = System::getContainer()->get('security.helper');
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$row = $operation->getRecord();
		$path = $projectDir . '/' . urldecode($row['id']);

		if (!is_dir($path))
		{
			if (!$security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_FILE) && !$security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_RECURSIVELY))
			{
				$operation->disable();
			}

			return;
		}

		$finder = Finder::create()->in($path);

		if ($finder->hasResults())
		{
			if (!$security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_RECURSIVELY))
			{
				$operation->disable();
			}
		}
		elseif (!$security->isGranted(ContaoCorePermissions::USER_CAN_DELETE_FILE))
		{
			$operation->disable();
		}
	}

	/**
	 * Adjust the edit file source button
	 */
	public function editSource(DataContainerOperation $operation)
	{
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FILE))
		{
			$operation->hide();

			return;
		}

		$row = $operation->getRecord();
		$strDecoded = rawurldecode($row['id']);
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		if (is_dir($projectDir . '/' . $strDecoded))
		{
			$operation->hide();

			return;
		}

		$objFile = new File($strDecoded);

		/** @var DC_Folder $dc */
		$dc = func_num_args() <= 12 ? null : func_get_arg(12);

		if (!in_array($objFile->extension, $dc->editableFileTypes ?? StringUtil::trimsplit(',', strtolower($GLOBALS['TL_DCA']['tl_files']['config']['editableFileTypes'] ?? System::getContainer()->getParameter('contao.editable_files')))))
		{
			$operation->disable();
		}
	}

	/**
	 * Adjust the show file button
	 */
	public function showFile(DataContainerOperation $operation)
	{
		if (Input::get('popup'))
		{
			$operation->hide();
		}
		else
		{
			$operation->setUrl(System::getContainer()->get('router')->generate('contao_backend_popup', array('src' => base64_encode($operation->getRecord()['id']))));
		}
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
		$blnDisable = $blnUnprotected && !is_file($projectDir . '/' . $strPath . '/.public');

		// Protect or unprotect the folder
		if (!$blnDisable && Input::post('FORM_SUBMIT') == 'tl_files')
		{
			if (Input::post($dc->inputName))
			{
				if (!$blnUnprotected)
				{
					$blnUnprotected = true;
					$objFolder->unprotect();

					(new Automator())->generateSymlinks();

					System::getContainer()->get('monolog.logger.contao.files')->info('Folder "' . $strPath . '" has been published');
				}
			}
			elseif ($blnUnprotected)
			{
				$blnUnprotected = false;
				$objFolder->protect();

				(new Automator())->generateSymlinks();

				System::getContainer()->get('monolog.logger.contao.files')->info('Folder "' . $strPath . '" has been protected');
			}
		}

		$class = ($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['tl_class'] ?? '') . ' cbx';
		$class = trim('widget ' . $class);

		return '
<div class="' . $class . '">
  <div id="ctrl_' . $dc->field . '" class="tl_checkbox_single_container">
    <span>
      <input type="hidden" name="' . $dc->inputName . '" value=""><input type="checkbox" name="' . $dc->inputName . '" id="opt_' . $dc->inputName . '_0" class="tl_checkbox" value="1"' . (($blnUnprotected || basename($strPath) == '__new__') ? ' checked="checked"' : '') . ' data-action="focus->contao--scroll-offset#store"' . ($blnDisable ? ' disabled' : '') . '>
      <label for="opt_' . $dc->inputName . '_0">' . $GLOBALS['TL_LANG']['tl_files']['protected'][0] . '</label>
    </span>
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

			$strName = basename($strPath);
			$strNewPath = str_replace($strName, Input::post('name'), $strPath, $count);

			if ($count > 0 && is_dir($projectDir . '/' . $strNewPath))
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

					System::getContainer()->get('monolog.logger.contao.files')->info('Synchronization of folder "' . $strPath . '" has been disabled');
				}
			}
			elseif ($blnUnsynchronized)
			{
				$blnUnsynchronized = false;
				$objFolder->synchronize();

				System::getContainer()->get('monolog.logger.contao.files')->info('Synchronization of folder "' . $strPath . '" has been enabled');
			}
		}

		$class = ($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['tl_class'] ?? '') . ' cbx';
		$class = trim('widget ' . $class);

		return '
<div class="' . $class . '">
  <div id="ctrl_' . $dc->field . '" class="tl_checkbox_single_container">
    <span>
      <input type="hidden" name="' . $dc->inputName . '" value=""><input type="checkbox" name="' . $dc->inputName . '" id="opt_' . $dc->inputName . '_0" class="tl_checkbox" value="1"' . ($blnUnsynchronized ? ' checked="checked"' : '') . ' data-action="focus->contao--scroll-offset#store"' . ($blnDisable ? ' disabled' : '') . '>
      <label for="opt_' . $dc->inputName . '_0">' . $GLOBALS['TL_LANG']['tl_files']['syncExclude'][0] . '</label>
    </span>
  </div>' . (Config::get('showHelp') ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_files']['syncExclude'][1] . '</p>' : '') . '
</div>';
	}
}
