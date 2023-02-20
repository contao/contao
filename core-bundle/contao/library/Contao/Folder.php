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
 * Creates, reads, writes and deletes folders
 *
 * Usage:
 *
 *     $folder = new Folder('test');
 *
 *     if (!$folder->isEmpty())
 *     {
 *         $folder->purge();
 *     }
 *
 * @property string  $name     The folder name
 * @property string  $basename Alias of $name
 * @property string  $dirname  The path of the parent folder
 * @property string  $filename The folder name
 * @property string  $path     The folder path
 * @property string  $value    Alias of $path
 * @property integer $size     The folder size
 */
class Folder extends System
{
	/**
	 * Folder name
	 * @var string
	 */
	protected $strFolder;

	/**
	 * Files model
	 * @var FilesModel
	 */
	protected $objModel;

	/**
	 * Root dir
	 * @var string
	 */
	protected $strRootDir;

	/**
	 * Pathinfo
	 * @var array
	 */
	protected $arrPathinfo = array();

	/**
	 * Scan cache
	 * @var array
	 */
	private static $arrScanCache = array();

	/**
	 * Check whether the folder exists
	 *
	 * @param string $strFolder The folder path
	 *
	 * @throws \Exception If $strFolder is not a folder
	 */
	public function __construct($strFolder)
	{
		// Handle open_basedir restrictions
		if ($strFolder == '.')
		{
			$strFolder = '';
		}

		$this->strRootDir = System::getContainer()->getParameter('kernel.project_dir');

		// Check whether it is a directory
		if (is_file($this->strRootDir . '/' . $strFolder))
		{
			throw new \Exception(sprintf('File "%s" is not a directory', $strFolder));
		}

		$this->import(Files::class, 'Files');
		$this->strFolder = $strFolder;

		// Create the folder if it does not exist
		if (!is_dir($this->strRootDir . '/' . $this->strFolder))
		{
			$strPath = '';
			$arrChunks = explode('/', $this->strFolder);

			// Create the folder
			foreach ($arrChunks as $strChunk)
			{
				$strPath .= ($strPath ? '/' : '') . $strChunk;
				$this->Files->mkdir($strPath);
			}

			// Update the database
			if (Dbafs::shouldBeSynchronized($this->strFolder))
			{
				$this->objModel = Dbafs::addResource($this->strFolder);
			}
		}
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey The property name
	 *
	 * @return mixed The property value
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'name':
			case 'basename':
				if (!isset($this->arrPathinfo[$strKey]))
				{
					$this->arrPathinfo = $this->getPathinfo();
				}

				return $this->arrPathinfo['basename'];

			case 'dirname':
			case 'filename':
				if (!isset($this->arrPathinfo[$strKey]))
				{
					$this->arrPathinfo = $this->getPathinfo();
				}

				return $this->arrPathinfo[$strKey];

			case 'path':
			case 'value':
				return $this->strFolder;

			case 'size':
				return $this->getSize();

			case 'ctime':
				return filectime($this->strRootDir . '/' . $this->strFolder);

			case 'mtime':
				return filemtime($this->strRootDir . '/' . $this->strFolder);

			case 'atime':
				return fileatime($this->strRootDir . '/' . $this->strFolder);

			default:
				return parent::__get($strKey);
		}
	}

	/**
	 * Return true if the folder is empty
	 *
	 * @return boolean True if the folder is empty
	 */
	public function isEmpty()
	{
		return \count(static::scan($this->strRootDir . '/' . $this->strFolder, true)) < 1;
	}

	/**
	 * Purge the folder
	 */
	public function purge()
	{
		$this->Files->rrdir($this->strFolder, true);

		// Update the database
		if (Dbafs::shouldBeSynchronized($this->strFolder))
		{
			$objFiles = FilesModel::findMultipleByBasepath($this->strFolder . '/');

			if ($objFiles !== null)
			{
				while ($objFiles->next())
				{
					$objFiles->delete();
				}
			}

			Dbafs::updateFolderHashes($this->strFolder);
		}
	}

	/**
	 * Delete the folder
	 */
	public function delete()
	{
		$this->Files->rrdir($this->strFolder);

		// Update the database
		if (Dbafs::shouldBeSynchronized($this->strFolder))
		{
			Dbafs::deleteResource($this->strFolder);
		}
	}

	/**
	 * Set the folder permissions
	 *
	 * @param string $intChmod The CHMOD settings
	 *
	 * @return boolean True if the operation was successful
	 */
	public function chmod($intChmod)
	{
		return $this->Files->chmod($this->strFolder, $intChmod);
	}

	/**
	 * Rename the folder
	 *
	 * @param string $strNewName The new path
	 *
	 * @return boolean True if the operation was successful
	 */
	public function renameTo($strNewName)
	{
		$strParent = \dirname($strNewName);

		// Create the parent folder if it does not exist
		if (!is_dir($this->strRootDir . '/' . $strParent))
		{
			new self($strParent);
		}

		$return = $this->Files->rename($this->strFolder, $strNewName);

		// Update the database AFTER the folder has been renamed
		$syncSource = Dbafs::shouldBeSynchronized($this->strFolder);
		$syncTarget = Dbafs::shouldBeSynchronized($strNewName);

		// Synchronize the database
		if ($syncSource && $syncTarget)
		{
			$this->objModel = Dbafs::moveResource($this->strFolder, $strNewName);
		}
		elseif ($syncSource)
		{
			$this->objModel = Dbafs::deleteResource($this->strFolder);
		}
		elseif ($syncTarget)
		{
			$this->objModel = Dbafs::addResource($strNewName);
		}

		// Reset the object AFTER the database has been updated
		if ($return)
		{
			$this->strFolder = $strNewName;
		}

		return $return;
	}

	/**
	 * Copy the folder
	 *
	 * @param string $strNewName The target path
	 *
	 * @return boolean True if the operation was successful
	 */
	public function copyTo($strNewName)
	{
		$strParent = \dirname($strNewName);

		// Create the parent folder if it does not exist
		if (!is_dir($this->strRootDir . '/' . $strParent))
		{
			new self($strParent);
		}

		$this->Files->rcopy($this->strFolder, $strNewName);

		// Update the database AFTER the folder has been renamed
		$syncSource = Dbafs::shouldBeSynchronized($this->strFolder);
		$syncTarget = Dbafs::shouldBeSynchronized($strNewName);

		if ($syncSource && $syncTarget)
		{
			Dbafs::copyResource($this->strFolder, $strNewName);
		}
		elseif ($syncTarget)
		{
			Dbafs::addResource($strNewName);
		}

		return true;
	}

	/**
	 * Protect the folder by removing the .public file
	 *
	 * @throws \RuntimeException If one of the parent folders is public
	 */
	public function protect()
	{
		if (!$this->isUnprotected())
		{
			return;
		}

		// Check if .public is a directory (see #3465)
		if (is_dir($this->strRootDir . '/' . $this->strFolder . '/.public'))
		{
			throw new \RuntimeException(sprintf('Cannot protect folder "%s" because it contains a directory called ".public"', $this->strFolder));
		}

		// Check if the .public file exists
		if (!is_file($this->strRootDir . '/' . $this->strFolder . '/.public'))
		{
			throw new \RuntimeException(sprintf('Cannot protect folder "%s" because one of its parent folders is public', $this->strFolder));
		}

		(new File($this->strFolder . '/.public'))->delete();
	}

	/**
	 * Unprotect the folder by adding a .public file
	 */
	public function unprotect()
	{
		// Check if .public is a directory (see #3465)
		if (is_dir($this->strRootDir . '/' . $this->strFolder . '/.public'))
		{
			throw new \RuntimeException(sprintf('Cannot unprotect folder "%s" because it contains a directory called ".public"', $this->strFolder));
		}

		if (!is_file($this->strRootDir . '/' . $this->strFolder . '/.public'))
		{
			(new Filesystem())->touch($this->strRootDir . '/' . $this->strFolder . '/.public');
		}
	}

	/**
	 * Check if the folder or any parent folder contains a .public file
	 *
	 * @return bool
	 */
	public function isUnprotected()
	{
		$path = $this->strFolder;

		do
		{
			if (is_file($this->strRootDir . '/' . $path . '/.public'))
			{
				return true;
			}

			$path = \dirname($path);
		}
		while ($path != '.');

		return false;
	}

	/**
	 * Synchronize the folder by removing the .nosync file
	 *
	 * @throws \RuntimeException If one of the parent folders is unsynchronized
	 */
	public function synchronize()
	{
		if (!$this->isUnsynchronized())
		{
			return;
		}

		// Check if the .nosync file exists
		if (!file_exists($this->strRootDir . '/' . $this->strFolder . '/.nosync'))
		{
			throw new \RuntimeException(sprintf('Cannot synchronize the folder "%s" because one of its parent folders is unsynchronized', $this->strFolder));
		}

		(new File($this->strFolder . '/.nosync'))->delete();
	}

	/**
	 * Unsynchronize the folder by adding a .nosync file
	 */
	public function unsynchronize()
	{
		if (!file_exists($this->strRootDir . '/' . $this->strFolder . '/.nosync'))
		{
			(new Filesystem())->touch($this->strRootDir . '/' . $this->strFolder . '/.nosync');
		}
	}

	/**
	 * Check if the folder or any parent folder contains a .nosync file
	 *
	 * @return bool
	 */
	public function isUnsynchronized()
	{
		$path = $this->strFolder;

		do
		{
			if (file_exists($this->strRootDir . '/' . $path . '/.nosync'))
			{
				return true;
			}

			$path = \dirname($path);
		}
		while ($path != '.');

		return false;
	}

	/**
	 * Return the files model
	 *
	 * @return FilesModel The files model
	 */
	public function getModel()
	{
		if ($this->objModel === null && Dbafs::shouldBeSynchronized($this->strFolder))
		{
			$this->objModel = FilesModel::findByPath($this->strFolder);
		}

		return $this->objModel;
	}

	/**
	 * Return the size of the folder
	 *
	 * @return integer The folder size in bytes
	 */
	protected function getSize()
	{
		$intSize = 0;

		foreach (static::scan($this->strRootDir . '/' . $this->strFolder, true) as $strFile)
		{
			if (strncmp($strFile, '.', 1) === 0)
			{
				continue;
			}

			if (is_dir($this->strRootDir . '/' . $this->strFolder . '/' . $strFile))
			{
				$objFolder = new self($this->strFolder . '/' . $strFile);
				$intSize += $objFolder->size;
			}
			else
			{
				$objFile = new File($this->strFolder . '/' . $strFile);
				$intSize += $objFile->size;
			}
		}

		return $intSize;
	}

	/**
	 * Return the path info (binary-safe)
	 *
	 * @return array The path info
	 *
	 * @see https://github.com/PHPMailer/PHPMailer/blob/master/class.phpmailer.php#L3520
	 */
	protected function getPathinfo()
	{
		$matches = array();
		$return = array('dirname'=>'', 'basename'=>'', 'extension'=>'', 'filename'=>'');

		preg_match('%^(.*?)[\\\\/]*([^/\\\\]*?)[\\\\/.]*$%m', $this->strFolder, $matches);

		if (isset($matches[1]))
		{
			$return['dirname'] = $this->strRootDir . '/' . $matches[1]; // see #8325
		}

		if (isset($matches[2]))
		{
			$return['basename'] = $matches[2];
			$return['filename'] = $matches[2];
		}

		return $return;
	}

	/**
	 * Scan a directory and return its files and folders as array
	 *
	 * @param string  $strFolder
	 * @param boolean $blnUncached
	 *
	 * @return array
	 */
	public static function scan($strFolder, $blnUncached=false): array
	{
		// Add a trailing slash
		if (substr($strFolder, -1, 1) != '/')
		{
			$strFolder .= '/';
		}

		// Load from cache
		if (!$blnUncached && isset(self::$arrScanCache[$strFolder]))
		{
			return self::$arrScanCache[$strFolder];
		}

		$arrReturn = array();

		// Scan directory
		if (is_dir($strFolder))
		{
			foreach (scandir($strFolder, SCANDIR_SORT_ASCENDING) as $strFile)
			{
				if ($strFile == '.' || $strFile == '..')
				{
					continue;
				}

				$arrReturn[] = $strFile;
			}
		}

		// Cache the result
		if (!$blnUncached)
		{
			self::$arrScanCache[$strFolder] = $arrReturn;
		}

		return $arrReturn;
	}
}
