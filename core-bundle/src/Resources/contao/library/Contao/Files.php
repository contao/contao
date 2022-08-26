<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Filesystem\Filesystem;

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
 */
class Files
{
	/**
	 * Object instance (Singleton)
	 * @var Files
	 */
	protected static $objInstance;

	/**
	 * Root dir
	 * @var string
	 */
	protected $strRootDir;

	/**
	 * Prevent direct instantiation (Singleton)
	 */
	protected function __construct()
	{
		$this->strRootDir = System::getContainer()->getParameter('kernel.project_dir');
	}

	/**
	 * Prevent cloning of the object (Singleton)
	 */
	final public function __clone()
	{
	}

	/**
	 * Instantiate the object (Factory)
	 *
	 * @return Files The files object
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

		if (file_exists($this->strRootDir . '/' . $strDirectory))
		{
			return true;
		}

		return mkdir($this->strRootDir . '/' . $strDirectory, 0777, true);
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

		if (!file_exists($this->strRootDir . '/' . $strDirectory))
		{
			return true;
		}

		return rmdir($this->strRootDir . '/' . $strDirectory);
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
		$arrFiles = Folder::scan($this->strRootDir . '/' . $strFolder, true);

		foreach ($arrFiles as $strFile)
		{
			if (is_link($this->strRootDir . '/' . $strFolder . '/' . $strFile))
			{
				$this->delete($strFolder . '/' . $strFile);
			}
			elseif (is_dir($this->strRootDir . '/' . $strFolder . '/' . $strFile))
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

		if ($strMode[0] != 'r' && ($strPath = \dirname($strFile)) && $strPath != '.')
		{
			$this->mkdir($strPath);
		}

		return fopen($this->strRootDir . '/' . $strFile, $strMode);
	}

	/**
	 * Write content to a file
	 *
	 * @param resource $resFile    The file handle
	 * @param string   $strContent The content to store in the file
	 */
	public function fputs($resFile, $strContent)
	{
		fwrite($resFile, $strContent);
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
		return fclose($resFile);
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
		if (\defined('PHP_WINDOWS_VERSION_BUILD') && file_exists($this->strRootDir . '/' . $strNewName) && strcasecmp($strOldName, $strNewName) !== 0)
		{
			$this->delete($strNewName);
		}

		$fs = new Filesystem();

		// Unix fix: rename case sensitively
		if (strcasecmp($strOldName, $strNewName) === 0 && strcmp($strOldName, $strNewName) !== 0)
		{
			$fs->rename($this->strRootDir . '/' . $strOldName, $this->strRootDir . '/' . $strOldName . '__', true);
			$strOldName .= '__';
		}

		$fs->rename($this->strRootDir . '/' . $strOldName, $this->strRootDir . '/' . $strNewName, true);

		return true;
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

		return copy($this->strRootDir . '/' . $strSource, $this->strRootDir . '/' . $strDestination);
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
		$arrFiles = Folder::scan($this->strRootDir . '/' . $strSource, true);

		foreach ($arrFiles as $strFile)
		{
			if (is_dir($this->strRootDir . '/' . $strSource . '/' . $strFile))
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
	 * Delete a file
	 *
	 * @param string $strFile The file name
	 *
	 * @return boolean True if the operation was successful
	 */
	public function delete($strFile)
	{
		$this->validate($strFile);

		return unlink($this->strRootDir . '/' . $strFile);
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

		return chmod($this->strRootDir . '/' . $strFile, $varMode);
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

		return is_writable($this->strRootDir . '/' . $strFile);
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

		return move_uploaded_file($strSource, $this->strRootDir . '/' . $strDestination);
	}

	/**
	 * Validate a path
	 *
	 * @throws \RuntimeException If the given paths are not valid
	 */
	protected function validate()
	{
		foreach (\func_get_args() as $strPath)
		{
			if (!$strPath)
			{
				throw new \RuntimeException('No file or folder name given'); // see #5795
			}

			if (Validator::isInsecurePath($strPath))
			{
				throw new \RuntimeException('Invalid file or folder name ' . $strPath);
			}
		}
	}
}

class_alias(Files::class, 'Files');
