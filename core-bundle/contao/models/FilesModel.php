<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\File\Metadata;
use Contao\Model\Collection;
use Contao\Model\Registry;
use Symfony\Component\Filesystem\Path;

/**
 * Reads and writes file entries
 *
 * The files themselves reside in the "files" directory. This class only handles
 * the corresponding database entries (database aided file system).
 *
 * @property integer           $id
 * @property string|null       $pid
 * @property integer           $tstamp
 * @property string|null       $uuid
 * @property string            $type
 * @property string            $path
 * @property string            $extension
 * @property string            $hash
 * @property boolean           $found
 * @property string            $name
 * @property float             $importantPartX
 * @property float             $importantPartY
 * @property float             $importantPartWidth
 * @property float             $importantPartHeight
 * @property string|array|null $meta
 *
 * @method static FilesModel|null findByIdOrAlias($val, array $opt=array())
 * @method static FilesModel|null findOneBy($col, $val, array $opt=array())
 * @method static FilesModel|null findOneByPid($val, array $opt=array())
 * @method static FilesModel|null findOneByTstamp($val, array $opt=array())
 * @method static FilesModel|null findOneByType($val, array $opt=array())
 * @method static FilesModel|null findOneByExtension($val, array $opt=array())
 * @method static FilesModel|null findOneByHash($val, array $opt=array())
 * @method static FilesModel|null findOneByFound($val, array $opt=array())
 * @method static FilesModel|null findOneByName($val, array $opt=array())
 * @method static FilesModel|null findOneByImportantPartX($val, array $opt=array())
 * @method static FilesModel|null findOneByImportantPartY($val, array $opt=array())
 * @method static FilesModel|null findOneByImportantPartWidth($val, array $opt=array())
 * @method static FilesModel|null findOneByImportantPartHeight($val, array $opt=array())
 * @method static FilesModel|null findOneByMeta($val, array $opt=array())
 *
 * @method static Collection|FilesModel[]|FilesModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByType($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByExtension($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByHash($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByFound($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByName($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByImportantPartX($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByImportantPartY($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByImportantPartWidth($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByImportantPartHeight($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findByMeta($val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|FilesModel[]|FilesModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByUuid($val, array $opt=array())
 * @method static integer countByType($val, array $opt=array())
 * @method static integer countByPath($val, array $opt=array())
 * @method static integer countByExtension($val, array $opt=array())
 * @method static integer countByHash($val, array $opt=array())
 * @method static integer countByFound($val, array $opt=array())
 * @method static integer countByName($val, array $opt=array())
 * @method static integer countByImportantPartX($val, array $opt=array())
 * @method static integer countByImportantPartY($val, array $opt=array())
 * @method static integer countByImportantPartWidth($val, array $opt=array())
 * @method static integer countByImportantPartHeight($val, array $opt=array())
 * @method static integer countByMeta($val, array $opt=array())
 */
class FilesModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_files';

	/**
	 * Returns the full absolute path.
	 */
	public function getAbsolutePath(): string
	{
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		return Path::makeAbsolute($this->path, $projectDir);
	}

	/**
	 * Find a file by its primary key
	 *
	 * @param mixed $varValue   The value
	 * @param array $arrOptions An optional options array
	 *
	 * @return FilesModel|Model|null The model or null if there is no file
	 */
	public static function findByPk($varValue, array $arrOptions=array())
	{
		if (static::$strPk == 'id')
		{
			return static::findById($varValue, $arrOptions);
		}

		return parent::findByPk($varValue, $arrOptions);
	}

	/**
	 * Find a file by its ID or UUID
	 *
	 * @param mixed $intId      The ID or UUID
	 * @param array $arrOptions An optional options array
	 *
	 * @return FilesModel|null The model or null if there is no file
	 */
	public static function findById($intId, array $arrOptions=array())
	{
		if (Validator::isUuid($intId))
		{
			return static::findByUuid($intId, $arrOptions);
		}

		return static::findOneBy('id', $intId, $arrOptions);
	}

	/**
	 * Find a file by its parent ID
	 *
	 * @param mixed $intPid     The parent ID
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no files
	 */
	public static function findByPid($intPid, array $arrOptions=array())
	{
		$t = static::$strTable;

		// Convert UUIDs to binary
		if (Validator::isStringUuid($intPid))
		{
			$intPid = StringUtil::uuidToBin($intPid);
		}

		return static::findBy(array("$t.pid=UNHEX(?)"), bin2hex((string) $intPid), $arrOptions);
	}

	/**
	 * Find multiple files by their IDs or UUIDs
	 *
	 * @param array $arrIds     An array of IDs or UUIDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no files
	 */
	public static function findMultipleByIds($arrIds, array $arrOptions=array())
	{
		if (empty($arrIds) || !\is_array($arrIds))
		{
			return null;
		}

		if (Validator::isUuid(current($arrIds)))
		{
			return static::findMultipleByUuids($arrIds, $arrOptions);
		}

		return parent::findMultipleByIds($arrIds, $arrOptions);
	}

	/**
	 * Find a file by its UUID
	 *
	 * @param string $strUuid    The UUID string
	 * @param array  $arrOptions An optional options array
	 *
	 * @return FilesModel|null The model or null if there is no file
	 */
	public static function findByUuid($strUuid, array $arrOptions=array())
	{
		$t = static::$strTable;

		// Convert UUIDs to binary
		if (Validator::isStringUuid($strUuid))
		{
			$strUuid = StringUtil::uuidToBin($strUuid);
		}

		// Check the model registry (does not work by default due to UNHEX())
		if (empty($arrOptions))
		{
			/** @var FilesModel $objModel */
			$objModel = Registry::getInstance()->fetch(static::$strTable, $strUuid, 'uuid');

			if ($objModel !== null)
			{
				return $objModel;
			}
		}

		return static::findOneBy(array("$t.uuid=UNHEX(?)"), bin2hex((string) $strUuid), $arrOptions);
	}

	/**
	 * Find multiple files by their UUIDs
	 *
	 * @param array $arrUuids   An array of UUIDs
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no files
	 */
	public static function findMultipleByUuids($arrUuids, array $arrOptions=array())
	{
		if (empty($arrUuids) || !\is_array($arrUuids))
		{
			return null;
		}

		$t = static::$strTable;

		foreach ($arrUuids as $k=>$v)
		{
			// Convert UUIDs to binary
			if (Validator::isStringUuid($v))
			{
				$v = StringUtil::uuidToBin($v);
			}

			$arrUuids[$k] = "UNHEX('" . bin2hex((string) $v) . "')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.uuid!=" . implode(", $t.uuid!=", $arrUuids);
		}

		return static::findBy(array("$t.uuid IN(" . implode(",", $arrUuids) . ")"), null, $arrOptions);
	}

	/**
	 * Find a file by its path
	 *
	 * @param string $path       The path
	 * @param array  $arrOptions An optional options array
	 *
	 * @return FilesModel|null The model or null if there is no file
	 */
	public static function findByPath($path, array $arrOptions=array())
	{
		if (!\is_string($path))
		{
			return null;
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$uploadPath = System::getContainer()->getParameter('contao.upload_path');

		if (Path::isBasePath($projectDir, $path))
		{
			$path = Path::makeRelative($path, $projectDir);
		}

		if (!Path::isBasePath($uploadPath, $path))
		{
			return null;
		}

		return static::findOneBy('path', $path, $arrOptions);
	}

	/**
	 * Find multiple files by their paths
	 *
	 * @param array $arrPaths   An array of file paths
	 * @param array $arrOptions An optional options array
	 *
	 * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no files
	 */
	public static function findMultipleByPaths($arrPaths, array $arrOptions=array())
	{
		if (empty($arrPaths) || !\is_array($arrPaths))
		{
			return null;
		}

		$t = static::$strTable;

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = Database::getInstance()->findInSet("$t.path", $arrPaths);
		}

		return static::findBy(array("$t.path IN(" . implode(',', array_fill(0, \count($arrPaths), '?')) . ")"), $arrPaths, $arrOptions);
	}

	/**
	 * Find multiple files with the same base path
	 *
	 * @param string $strPath    The base path
	 * @param array  $arrOptions An optional options array
	 *
	 * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no matching files
	 */
	public static function findMultipleByBasepath($strPath, array $arrOptions=array())
	{
		$t = static::$strTable;

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.path";
		}

		return static::findBy(array("$t.path LIKE ?"), $strPath . '%', $arrOptions);
	}

	/**
	 * Find multiple files by UUID and a list of extensions
	 *
	 * @param array $arrUuids      An array of file UUIDs
	 * @param array $arrExtensions An array of file extensions
	 * @param array $arrOptions    An optional options array
	 *
	 * @return Collection|FilesModel[]|FilesModel|null A collection of models or null of there are no matching files
	 */
	public static function findMultipleByUuidsAndExtensions($arrUuids, $arrExtensions, array $arrOptions=array())
	{
		if (empty($arrUuids) || empty($arrExtensions) || !\is_array($arrUuids) || !\is_array($arrExtensions))
		{
			return null;
		}

		foreach ($arrExtensions as $k=>$v)
		{
			if (!preg_match('/^[a-z0-9]{2,5}$/i', $v))
			{
				unset($arrExtensions[$k]);
			}
		}

		$t = static::$strTable;

		foreach ($arrUuids as $k=>$v)
		{
			// Convert UUIDs to binary
			if (Validator::isStringUuid($v))
			{
				$v = StringUtil::uuidToBin($v);
			}

			$arrUuids[$k] = "UNHEX('" . bin2hex((string) $v) . "')";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.uuid!=" . implode(", $t.uuid!=", $arrUuids);
		}

		return static::findBy(array("$t.uuid IN(" . implode(",", $arrUuids) . ") AND $t.extension IN('" . implode("','", $arrExtensions) . "')"), null, $arrOptions);
	}

	/**
	 * Find all files in a folder
	 *
	 * @param string $strPath    The folder path
	 * @param array  $arrOptions An optional options array
	 *
	 * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no matching files
	 */
	public static function findMultipleFilesByFolder($strPath, array $arrOptions=array())
	{
		$t = static::$strTable;
		$strPath = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $strPath);

		return static::findBy(array("$t.type='file' AND $t.path LIKE ? AND $t.path NOT LIKE ?"), array($strPath . '/%', $strPath . '/%/%'), $arrOptions);
	}

	/**
	 * Find all folders in a folder
	 *
	 * @param string $strPath    The folder path
	 * @param array  $arrOptions An optional options array
	 *
	 * @return Collection|FilesModel[]|FilesModel|null A collection of models or null if there are no matching folders
	 */
	public static function findMultipleFoldersByFolder($strPath, array $arrOptions=array())
	{
		$t = static::$strTable;
		$strPath = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $strPath);

		return static::findBy(array("$t.type='folder' AND $t.path LIKE ? AND $t.path NOT LIKE ?"), array($strPath . '/%', $strPath . '/%/%'), $arrOptions);
	}

	/**
	 * Do not reload the data upon insert
	 *
	 * @param integer $intType The query type (Model::INSERT or Model::UPDATE)
	 */
	protected function postSave($intType)
	{
	}

	/**
	 * Return the meta fields defined in tl_files.meta.eval.metaFields
	 */
	public static function getMetaFields(): array
	{
		Controller::loadDataContainer('tl_files');

		return array_keys($GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] ?? array());
	}

	/**
	 * Return the metadata for this file
	 *
	 * Returns the metadata of the first matching locale or null if none was found.
	 */
	public function getMetadata(string ...$locales): Metadata|null
	{
		$dataCollection = StringUtil::deserialize($this->meta, true);

		foreach ($locales as $locale)
		{
			if (!\is_array($data = $dataCollection[$locale] ?? null))
			{
				continue;
			}

			// Make sure we resolve insert tags pointing to files
			if (isset($data[Metadata::VALUE_URL]))
			{
				$data[Metadata::VALUE_URL] = System::getContainer()->get('contao.insert_tag.parser')->replaceInline($data[Metadata::VALUE_URL] ?? '');
			}

			// Fill missing meta fields with empty values
			$metaFields = self::getMetaFields();
			$data = array_merge(array_combine($metaFields, array_fill(0, \count($metaFields), '')), $data);

			return new Metadata($data);
		}

		return null;
	}
}
