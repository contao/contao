<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Library
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao;


/**
 * A class to access the file system
 *
 * The class handles file operations via the PHP functions.
 *
 * Usage:
 *
 *     $files = Files::getInstance();
 *
 *     $files->mkdir('test');
 *
 *     $files->fopen('test/one.txt', 'wb');
 *     $files->fputs('My test');
 *     $files->fclose();
 *
 *     $files->rrdir('test');
 *
 * @package   Library
 * @author    Leo Feyer <https://github.com/leofeyer>
 * @copyright Leo Feyer 2005-2014
 */
class Files
{

	/**
	 * Object instance (Singleton)
	 * @var \Files
	 */
	protected static $objInstance;


	/**
	 * Prevent direct instantiation (Singleton)
	 */
	protected function __construct() {}


	/**
	 * Prevent cloning of the object (Singleton)
	 */
	final public function __clone() {}


	/**
	 * Instantiate the object (Factory)
	 *
	 * @return \Files The files object
	 */
	public static function getInstance()
	{
		if (self::$objInstance === null)
		{
			self::$objInstance = new static();
		}

		return self::$objInstance;
	}


	/**
	 * Create a directory
	 *
	 * @param string $strDirectory The directory name
	 *
	 * @return boolean True if the operation was successful
	 */
	public function mkdir($strDirectory)
	{
		$this->validate($strDirectory);
		return @mkdir(TL_ROOT . '/' . $strDirectory);
	}


	/**
	 * Remove a directory
	 *
	 * @param string $strDirectory The directory name
	 *
	 * @return boolean True if the operation was successful
	 */
	public function rmdir($strDirectory)
	{
		$this->validate($strDirectory);
		return @rmdir(TL_ROOT. '/' . $strDirectory);
	}


	/**
	 * Recursively remove a directory
	 *
	 * @param string  $strFolder       The directory name
	 * @param boolean $blnPreserveRoot If true, the root folder will not be removed
	 */
	public function rrdir($strFolder, $blnPreserveRoot=false)
	{
		$this->validate($strFolder);
		$arrFiles = scan(TL_ROOT . '/' . $strFolder, true);

		foreach ($arrFiles as $strFile)
		{
			if (is_link(TL_ROOT . '/' . $strFolder . '/' . $strFile))
			{
				$this->delete($strFolder . '/' . $strFile);
			}
			elseif (is_dir(TL_ROOT . '/' . $strFolder . '/' . $strFile))
			{
				$this->rrdir($strFolder . '/' . $strFile);
			}
			else
			{
				$this->delete($strFolder . '/' . $strFile);
			}
		}

		if (!$blnPreserveRoot)
		{
			$this->rmdir($strFolder);
		}
	}


	/**
	 * Open a file and return the handle
	 *
	 * @param string $strFile The file name
	 * @param string $strMode The operation mode
	 *
	 * @return resource The file handle
	 */
	public function fopen($strFile, $strMode)
	{
		$this->validate($strFile);
		return @fopen(TL_ROOT . '/' . $strFile, $strMode);
	}


	/**
	 * Write content to a file
	 *
	 * @param resource $resFile    The file handle
	 * @param string   $strContent The content to store in the file
	 */
	public function fputs($resFile, $strContent)
	{
		@fputs($resFile, $strContent);
	}


	/**
	 * Close a file handle
	 *
	 * @param resource $resFile The file handle
	 *
	 * @return boolean True if the operation was successful
	 */
	public function fclose($resFile)
	{
		return @fclose($resFile);
	}


	/**
	 * Rename a file or folder
	 *
	 * @param string $strOldName The old name
	 * @param string $strNewName The new name
	 *
	 * @return boolean True if the operation was successful
	 */
	public function rename($strOldName, $strNewName)
	{
		// Source file == target file
		if ($strOldName == $strNewName)
		{
			return true;
		}

		$this->validate($strOldName, $strNewName);

		// Windows fix: delete the target file
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && file_exists(TL_ROOT . '/' . $strNewName))
		{
			$this->delete($strNewName);
		}

		// Unix fix: rename case sensitively
		if (strcasecmp($strOldName, $strNewName) === 0 && strcmp($strOldName, $strNewName) !== 0)
		{
			@rename(TL_ROOT . '/' . $strOldName, TL_ROOT . '/' . $strOldName . '__');
			$strOldName .= '__';
		}

		return @rename(TL_ROOT . '/' . $strOldName, TL_ROOT . '/' . $strNewName);
	}


	/**
	 * Copy a file or folder
	 *
	 * @param string $strSource      The source file or folder
	 * @param string $strDestination The new file or folder path
	 *
	 * @return boolean True if the operation was successful
	 */
	public function copy($strSource, $strDestination)
	{
		$this->validate($strSource, $strDestination);
		return @copy(TL_ROOT . '/' . $strSource, TL_ROOT . '/' . $strDestination);
	}


	/**
	 * Recursively copy a directory
	 *
	 * @param string $strSource      The source file or folder
	 * @param string $strDestination The new file or folder path
	 */
	public function rcopy($strSource, $strDestination)
	{
		$this->validate($strSource, $strDestination);

		$this->mkdir($strDestination);
		$arrFiles = scan(TL_ROOT . '/' . $strSource, true);

		foreach ($arrFiles as $strFile)
		{
			if (is_dir(TL_ROOT . '/' . $strSource . '/' . $strFile))
			{
				$this->rcopy($strSource . '/' . $strFile, $strDestination . '/' . $strFile);
			}
			else
			{
				$this->copy($strSource . '/' . $strFile, $strDestination . '/' . $strFile);
			}
		}
	}


	/**
	 * Generate a symlink
	 *
	 * @param string $strSource The symlink name
	 * @param string $strTarget The symlink target
	 *
	 * @throws \Exception If the symlink cannot be created
	 */
	public function symlink($strSource, $strTarget)
	{
		// Check the source
		if ($strSource == '')
		{
			throw new \Exception('No symlink name provided');
		}

		// Check the target
		if ($strTarget == '')
		{
			throw new \Exception('No symlink target provided');
		}
		elseif (strpos($strTarget, '../') !== false)
		{
			throw new \Exception('The symlink target must not be relative');
		}

		// Remove an existing symlink
		if (file_exists(TL_ROOT . '/' . $strTarget))
		{
			if (!is_link(TL_ROOT . '/' . $strTarget))
			{
				throw new \Exception("The target $strTarget exists and is not a symlink");
			}

			unlink(TL_ROOT . '/' . $strTarget);
		}

		$strParent = dirname($strTarget);

		// Create the parent folder
		if (!is_dir(TL_ROOT . '/' . $strParent))
		{
			mkdir(TL_ROOT . '/' . $strParent, 0777, true);
		}

		// Create the symlink
		symlink($strSource, TL_ROOT . '/' . $strTarget);

		// Get the symlink stats
		$stat = lstat(TL_ROOT . '/' . $strTarget);

		// Try to fix the UID
		if ($stat['uid'] != getmyuid())
		{
			if (function_exists('lchown'))
			{
				lchown(TL_ROOT . '/' . $strTarget, getmyuid());
			}
		}

		// Try to fix the GID
		if ($stat['gid'] != getmygid())
		{
			if (function_exists('lchgrp'))
			{
				lchgrp(TL_ROOT . '/' . $strTarget, getmygid());
			}
		}
	}


	/**
	 * Delete a file
	 *
	 * @param string $strFile The file name
	 *
	 * @return boolean True if the operation was successful
	 */
	public function delete($strFile)
	{
		$this->validate($strFile);
		return @unlink(TL_ROOT . '/' . $strFile);
	}


	/**
	 * Change the file mode
	 *
	 * @param string $strFile The file name
	 * @param mixed  $varMode The new file mode
	 *
	 * @return boolean True if the operation was successful
	 */
	public function chmod($strFile, $varMode)
	{
		$this->validate($strFile);
		return @chmod(TL_ROOT . '/' . $strFile, $varMode);
	}


	/**
	 * Check whether a file is writeable
	 *
	 * @param string $strFile The file name
	 *
	 * @return boolean True if the file is writeable
	 */
	public function is_writeable($strFile)
	{
		$this->validate($strFile);
		return @is_writeable(TL_ROOT . '/' . $strFile);
	}


	/**
	 * Move an uploaded file to a folder
	 *
	 * @param string $strSource      The source file
	 * @param string $strDestination The new file path
	 *
	 * @return boolean True if the operation was successful
	 */
	public function move_uploaded_file($strSource, $strDestination)
	{
		$this->validate($strSource, $strDestination);
		return @move_uploaded_file($strSource, TL_ROOT . '/' . $strDestination);
	}


	/**
	 * Validate a path
	 *
	 * @throws \RuntimeException If the given paths are not valid
	 */
	protected function validate()
	{
		foreach (func_get_args() as $strPath)
		{
			if ($strPath == '') // see #5795
			{
				throw new \RuntimeException('No file or folder name given');
			}
			elseif (\Validator::isInsecurePath($strPath))
			{
				throw new \RuntimeException('Invalid file or folder name ' . $strPath);
			}
		}
	}
}
