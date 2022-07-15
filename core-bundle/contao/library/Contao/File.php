<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\Image\DeferredImageInterface;
use Contao\Image\ImageDimensions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\String\UnicodeString;

/**
 * Creates, reads, writes and deletes files
 *
 * Usage:
 *
 *     $file = new File('test.txt');
 *     $file->write('This is a test');
 *     $file->close();
 *
 *     $file->delete();
 *
 *     File::putContent('test.txt', 'This is a test');
 *
 * @property integer  $size          The file size
 * @property integer  $filesize      Alias of $size
 * @property string   $name          The file name and extension
 * @property string   $basename      Alias of $name
 * @property string   $dirname       The path of the parent folder
 * @property string   $extension     The lowercase file extension
 * @property string   $origext       The original file extension
 * @property string   $filename      The file name without extension
 * @property string   $tmpname       The name of the temporary file
 * @property string   $path          The file path
 * @property string   $value         Alias of $path
 * @property string   $mime          The mime type
 * @property string   $hash          The MD5 checksum
 * @property string   $ctime         The ctime
 * @property string   $mtime         The mtime
 * @property string   $atime         The atime
 * @property string   $icon          The mime icon name
 * @property string   $dataUri       The data URI
 * @property array    $imageSize     The file dimensions (images only)
 * @property integer  $width         The file width (images only)
 * @property integer  $height        The file height (images only)
 * @property array    $imageViewSize The viewbox dimensions
 * @property integer  $viewWidth     The viewbox width
 * @property integer  $viewHeight    The viewbox height
 * @property boolean  $isImage       True if the file is an image
 * @property boolean  $isGdImage     True if the file can be handled by the GDlib
 * @property boolean  $isSvgImage    True if the file is an SVG image
 * @property integer  $channels      The number of channels (images only)
 * @property integer  $bits          The number of bits for each color (images only)
 * @property boolean  $isRgbImage    True if the file is an RGB image
 * @property boolean  $isCmykImage   True if the file is a CMYK image
 * @property resource $handle        The file handle (returned by fopen())
 * @property string   $title         The file title
 */
class File extends System
{
	/**
	 * File handle
	 * @var resource
	 */
	protected $resFile;

	/**
	 * File name
	 * @var string
	 */
	protected $strFile;

	/**
	 * Temp name
	 * @var string
	 */
	protected $strTmp;

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
	 * Image size
	 * @var array
	 */
	protected $arrImageSize = array();

	/**
	 * Image size runtime cache
	 * @var array
	 */
	protected static $arrImageSizeCache = array();

	/**
	 * Image view size
	 * @var array
	 */
	protected $arrImageViewSize = array();

	/**
	 * Instantiate a new file object
	 *
	 * @param string $strFile The file path
	 *
	 * @throws \Exception If $strFile is a directory
	 */
	public function __construct($strFile)
	{
		// Handle open_basedir restrictions
		if ($strFile == '.')
		{
			$strFile = '';
		}

		$this->strRootDir = System::getContainer()->getParameter('kernel.project_dir');

		// Make sure we are not pointing to a directory
		if (is_dir($this->strRootDir . '/' . $strFile))
		{
			throw new \Exception(sprintf('Directory "%s" is not a file', $strFile));
		}

		$this->import(Files::class, 'Files');

		$this->strFile = $strFile;
	}

	/**
	 * Close the file handle if it has not been done yet
	 */
	public function __destruct()
	{
		if (\is_resource($this->resFile))
		{
			$this->Files->fclose($this->resFile);
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
			case 'size':
			case 'filesize':
				return filesize($this->strRootDir . '/' . $this->strFile);

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

			case 'extension':
				if (!isset($this->arrPathinfo['extension']))
				{
					$this->arrPathinfo = $this->getPathinfo();
				}

				return strtolower($this->arrPathinfo['extension']);

			case 'origext':
				if (!isset($this->arrPathinfo['extension']))
				{
					$this->arrPathinfo = $this->getPathinfo();
				}

				return $this->arrPathinfo['extension'];

			case 'tmpname':
				return basename($this->strTmp);

			case 'path':
			case 'value':
				return $this->strFile;

			case 'mime':
				return $this->getMimeType();

			case 'hash':
				return $this->getHash();

			case 'ctime':
				return filectime($this->strRootDir . '/' . $this->strFile);

			case 'mtime':
				return filemtime($this->strRootDir . '/' . $this->strFile);

			case 'atime':
				return fileatime($this->strRootDir . '/' . $this->strFile);

			case 'icon':
				return $this->getIcon();

			case 'dataUri':
				if ($this->extension == 'svgz')
				{
					return 'data:' . $this->mime . ';base64,' . base64_encode(gzdecode($this->getContent()));
				}

				return 'data:' . $this->mime . ';base64,' . base64_encode($this->getContent());

			case 'imageSize':
				if (empty($this->arrImageSize))
				{
					$strCacheKey = $this->strFile . '|' . ($this->exists() ? $this->mtime : 0);

					if (isset(static::$arrImageSizeCache[$strCacheKey]))
					{
						$this->arrImageSize = static::$arrImageSizeCache[$strCacheKey];
					}
					else
					{
						$imageFactory = System::getContainer()->get('contao.image.factory');

						try
						{
							$dimensions = $imageFactory->create($this->strRootDir . '/' . $this->strFile)->getDimensions();

							if (!$dimensions->isRelative() && !$dimensions->isUndefined())
							{
								$mapper = array
								(
									'gif' => IMAGETYPE_GIF,
									'jpg' => IMAGETYPE_JPEG,
									'jpeg' => IMAGETYPE_JPEG,
									'png' => IMAGETYPE_PNG,
									'webp' => IMAGETYPE_WEBP,
									'avif' => \defined('IMAGETYPE_AVIF') ? IMAGETYPE_AVIF : 19,
									'heic' => IMAGETYPE_UNKNOWN, // TODO: replace with IMAGETYPE_HEIC once available
									'jxl' => IMAGETYPE_UNKNOWN, // TODO: replace with IMAGETYPE_JXL once available
								);

								$this->arrImageSize = array
								(
									$dimensions->getSize()->getWidth(),
									$dimensions->getSize()->getHeight(),
									$mapper[$this->extension] ?? 0,
									'width="' . $dimensions->getSize()->getWidth() . '" height="' . $dimensions->getSize()->getHeight() . '"',
									'bits' => 8,
									'channels' => 3,
									'mime' => $this->getMimeType()
								);
							}
						}
						catch (\Exception $e)
						{
							// ignore
						}
					}

					if (!isset(static::$arrImageSizeCache[$strCacheKey]))
					{
						static::$arrImageSizeCache[$strCacheKey] = $this->arrImageSize;
					}
				}

				return $this->arrImageSize;

			case 'width':
				return $this->imageSize[0] ?? null;

			case 'height':
				return $this->imageSize[1] ?? null;

			case 'imageViewSize':
				if (empty($this->arrImageViewSize))
				{
					if ($this->imageSize)
					{
						$this->arrImageViewSize = array
						(
							$this->imageSize[0],
							$this->imageSize[1]
						);
					}
					elseif ($this->isSvgImage)
					{
						try
						{
							$dimensions = new ImageDimensions(
								System::getContainer()
									->get('contao.image.imagine_svg')
									->open($this->strRootDir . '/' . $this->strFile)
									->getSize()
							);

							$this->arrImageViewSize = array
							(
								$dimensions->getSize()->getWidth(),
								$dimensions->getSize()->getHeight()
							);

							if (!$this->arrImageViewSize[0] || !$this->arrImageViewSize[1] || $dimensions->isUndefined())
							{
								$this->arrImageViewSize = false;
							}
						}
						catch (\Exception $e)
						{
							$this->arrImageViewSize = false;
						}
					}
				}

				return $this->arrImageViewSize;

			case 'viewWidth':
				// Store in variable as empty() calls __isset() which is not implemented and thus always true
				$imageViewSize = $this->imageViewSize;

				return !empty($imageViewSize) ? $imageViewSize[0] : null;

			case 'viewHeight':
				// Store in variable as empty() calls __isset() which is not implemented and thus always true
				$imageViewSize = $this->imageViewSize;

				return !empty($imageViewSize) ? $imageViewSize[1] : null;

			case 'isImage':
				return $this->isGdImage || $this->isSvgImage;

			case 'isGdImage':
				return \in_array($this->extension, array('gif', 'jpg', 'jpeg', 'png', 'webp', 'avif', 'heic', 'jxl'));

			case 'isSvgImage':
				return \in_array($this->extension, array('svg', 'svgz'));

			case 'channels':
				return $this->imageSize['channels'];

			case 'bits':
				return $this->imageSize['bits'];

			case 'isRgbImage':
				return $this->channels == 3;

			case 'isCmykImage':
				return $this->channels == 4;

			case 'handle':
				if (!\is_resource($this->resFile))
				{
					$this->resFile = fopen($this->strRootDir . '/' . $this->strFile, 'r');
				}

				return $this->resFile;

			default:
				return parent::__get($strKey);
		}
	}

	/**
	 * Create the file if it does not yet exist
	 *
	 * @throws \Exception If the file cannot be written
	 */
	protected function createIfNotExists()
	{
		// The file exists
		if (file_exists($this->strRootDir . '/' . $this->strFile))
		{
			return;
		}

		// Handle open_basedir restrictions
		if (($strFolder = \dirname($this->strFile)) == '.')
		{
			$strFolder = '';
		}

		// Create the folder
		if (!is_dir($this->strRootDir . '/' . $strFolder))
		{
			new Folder($strFolder);
		}

		// Open the file
		if (!$this->resFile = $this->Files->fopen($this->strFile, 'wb'))
		{
			throw new \Exception(sprintf('Cannot create file "%s"', $this->strFile));
		}
	}

	/**
	 * Check whether the file exists
	 *
	 * @return boolean True if the file exists
	 */
	public function exists()
	{
		return file_exists($this->strRootDir . '/' . $this->strFile);
	}

	/**
	 * Truncate the file and reset the file pointer
	 *
	 * @return boolean True if the operation was successful
	 */
	public function truncate()
	{
		if (\is_resource($this->resFile))
		{
			ftruncate($this->resFile, 0);
			rewind($this->resFile);
		}

		return $this->write('');
	}

	/**
	 * Write data to the file
	 *
	 * @param mixed $varData The data to be written
	 *
	 * @return boolean True if the operation was successful
	 */
	public function write($varData)
	{
		return $this->fputs($varData, 'wb');
	}

	/**
	 * Append data to the file
	 *
	 * @param mixed  $varData The data to be appended
	 * @param string $strLine The line ending (defaults to LF)
	 *
	 * @return boolean True if the operation was successful
	 */
	public function append($varData, $strLine="\n")
	{
		return $this->fputs($varData . $strLine, 'ab');
	}

	/**
	 * Prepend data to the file
	 *
	 * @param mixed  $varData The data to be prepended
	 * @param string $strLine The line ending (defaults to LF)
	 *
	 * @return boolean True if the operation was successful
	 */
	public function prepend($varData, $strLine="\n")
	{
		return $this->fputs($varData . $strLine . $this->getContent(), 'wb');
	}

	/**
	 * Delete the file
	 *
	 * @return boolean True if the operation was successful
	 */
	public function delete()
	{
		$return = $this->Files->delete($this->strFile);

		// Update the database
		if (Dbafs::shouldBeSynchronized($this->strFile))
		{
			Dbafs::deleteResource($this->strFile);
		}

		return $return;
	}

	/**
	 * Set the file permissions
	 *
	 * @param integer $intChmod The CHMOD settings
	 *
	 * @return boolean True if the operation was successful
	 */
	public function chmod($intChmod)
	{
		return $this->Files->chmod($this->strFile, $intChmod);
	}

	/**
	 * Close the file handle
	 *
	 * @return boolean True if the operation was successful
	 */
	public function close()
	{
		if (\is_resource($this->resFile))
		{
			$this->Files->fclose($this->resFile);
		}

		// Create the file path
		if (!file_exists($this->strRootDir . '/' . $this->strFile))
		{
			// Handle open_basedir restrictions
			if (($strFolder = \dirname($this->strFile)) == '.')
			{
				$strFolder = '';
			}

			// Create the parent folder
			if (!is_dir($this->strRootDir . '/' . $strFolder))
			{
				new Folder($strFolder);
			}
		}

		// Move the temporary file to its destination
		$return = $this->Files->rename($this->strTmp, $this->strFile);
		$this->strTmp = null;

		// Update the database
		if (Dbafs::shouldBeSynchronized($this->strFile))
		{
			$this->objModel = Dbafs::addResource($this->strFile);
		}

		return $return;
	}

	/**
	 * Return the files model
	 *
	 * @return FilesModel The files model
	 */
	public function getModel()
	{
		if ($this->objModel === null && Dbafs::shouldBeSynchronized($this->strFile))
		{
			$this->objModel = FilesModel::findByPath($this->strFile);
		}

		return $this->objModel;
	}

	/**
	 * Generate the image if the current file is a deferred image and does not exist yet
	 *
	 * @return bool True if a deferred image was resized otherwise false
	 */
	public function createIfDeferred()
	{
		if (!$this->exists())
		{
			try
			{
				$image = System::getContainer()->get('contao.image.factory')->create($this->strRootDir . '/' . $this->strFile);

				if ($image instanceof DeferredImageInterface)
				{
					System::getContainer()->get('contao.image.resizer')->resizeDeferredImage($image);

					return true;
				}
			}
			catch (\Throwable $e)
			{
				// ignore
			}
		}

		return false;
	}

	/**
	 * Return the file content as string
	 *
	 * @return string The file content without BOM
	 */
	public function getContent()
	{
		$this->createIfDeferred();

		$strContent = file_get_contents($this->strRootDir . '/' . ($this->strTmp ?: $this->strFile));

		// Remove BOMs (see #4469)
		if (strncmp($strContent, "\xEF\xBB\xBF", 3) === 0)
		{
			$strContent = substr($strContent, 3);
		}
		elseif (strncmp($strContent, "\xFF\xFE", 2) === 0)
		{
			$strContent = substr($strContent, 2);
		}
		elseif (strncmp($strContent, "\xFE\xFF", 2) === 0)
		{
			$strContent = substr($strContent, 2);
		}

		return $strContent;
	}

	/**
	 * Write to a file
	 *
	 * @param string $strFile    Relative file name
	 * @param string $strContent Content to be written
	 */
	public static function putContent($strFile, $strContent)
	{
		$objFile = new static($strFile);
		$objFile->write($strContent);
		$objFile->close();
	}

	/**
	 * Return the file content as array
	 *
	 * @return array The file content as array
	 */
	public function getContentAsArray()
	{
		return array_map('rtrim', file($this->strRootDir . '/' . $this->strFile));
	}

	/**
	 * Rename the file
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
			new Folder($strParent);
		}

		$return = $this->Files->rename($this->strFile, $strNewName);

		// Update the database AFTER the file has been renamed
		$syncSource = Dbafs::shouldBeSynchronized($this->strFile);
		$syncTarget = Dbafs::shouldBeSynchronized($strNewName);

		// Synchronize the database
		if ($syncSource && $syncTarget)
		{
			$this->objModel = Dbafs::moveResource($this->strFile, $strNewName);
		}
		elseif ($syncSource)
		{
			$this->objModel = Dbafs::deleteResource($this->strFile);
		}
		elseif ($syncTarget)
		{
			$this->objModel = Dbafs::addResource($strNewName);
		}

		// Reset the object AFTER the database has been updated
		if ($return)
		{
			$this->strFile = $strNewName;
			$this->arrImageSize = array();
			$this->arrPathinfo = array();
		}

		return $return;
	}

	/**
	 * Copy the file
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
			new Folder($strParent);
		}

		$return = $this->Files->copy($this->strFile, $strNewName);

		// Update the database AFTER the file has been renamed
		$syncSource = Dbafs::shouldBeSynchronized($this->strFile);
		$syncTarget = Dbafs::shouldBeSynchronized($strNewName);

		// Synchronize the database
		if ($syncSource && $syncTarget)
		{
			Dbafs::copyResource($this->strFile, $strNewName);
		}
		elseif ($syncTarget)
		{
			Dbafs::addResource($strNewName);
		}

		return $return;
	}

	/**
	 * Resize the file if it is an image
	 *
	 * @param integer $width  The target width
	 * @param integer $height The target height
	 * @param string  $mode   The resize mode
	 *
	 * @return boolean True if the image could be resized successfully
	 */
	public function resizeTo($width, $height, $mode='')
	{
		if (!$this->isImage)
		{
			return false;
		}

		System::getContainer()
			->get('contao.image.factory')
			->create($this->strRootDir . '/' . $this->strFile, array($width, $height, $mode), $this->strRootDir . '/' . $this->strFile)
		;

		$this->arrPathinfo = array();
		$this->arrImageSize = array();

		// Clear the image size cache as mtime could potentially not change
		unset(static::$arrImageSizeCache[$this->strFile . '|' . $this->mtime]);

		return true;
	}

	/**
	 * Send the file to the browser
	 *
	 * @param string  $filename An optional filename
	 * @param boolean $inline   Show the file in the browser instead of opening the download dialog
	 *
	 * @throws ResponseException
	 */
	public function sendToBrowser($filename='', $inline=false)
	{
		$response = new BinaryFileResponse($this->strRootDir . '/' . $this->strFile);
		$response->setPrivate(); // public by default
		$response->setAutoEtag();

		$response->setContentDisposition
		(
			$inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
			$filename,
			(new UnicodeString($this->basename))->ascii()->toString()
		);

		$response->headers->addCacheControlDirective('must-revalidate');
		$response->headers->set('Connection', 'close');
		$response->headers->set('Content-Type', $this->getMimeType());

		throw new ResponseException($response);
	}

	/**
	 * Check if any parent folder contains a .public file
	 *
	 * @return bool
	 */
	public function isUnprotected()
	{
		return (new Folder(\dirname($this->strFile)))->isUnprotected();
	}

	/**
	 * Write data to a file
	 *
	 * @param mixed  $varData The data to be written
	 * @param string $strMode The operation mode
	 *
	 * @return boolean True if the operation was successful
	 */
	protected function fputs($varData, $strMode)
	{
		if (!\is_resource($this->resFile))
		{
			$this->strTmp = 'system/tmp/' . md5(uniqid(mt_rand(), true));

			// Copy the contents of the original file to append data
			if (strncmp($strMode, 'a', 1) === 0 && file_exists($this->strRootDir . '/' . $this->strFile))
			{
				$this->Files->copy($this->strFile, $this->strTmp);
			}

			// Open the temporary file
			if (!$this->resFile = $this->Files->fopen($this->strTmp, $strMode))
			{
				return false;
			}
		}

		fwrite($this->resFile, $varData);

		return true;
	}

	/**
	 * Return the mime type and icon of the file based on its extension
	 *
	 * @return array An array with mime type and icon name
	 */
	protected function getMimeInfo()
	{
		return $GLOBALS['TL_MIME'][$this->extension] ?? array('application/octet-stream', 'iconPLAIN.svg');
	}

	/**
	 * Get the mime type of the file based on its extension
	 *
	 * @return string The mime type
	 */
	protected function getMimeType()
	{
		$arrMime = $this->getMimeInfo();

		return $arrMime[0];
	}

	/**
	 * Return the file icon depending on the file type
	 *
	 * @return string The icon name
	 */
	protected function getIcon()
	{
		$arrMime = $this->getMimeInfo();

		return $arrMime[1];
	}

	/**
	 * Return the MD5 hash of the file
	 *
	 * @return string The MD5 hash
	 */
	protected function getHash()
	{
		return md5_file($this->strRootDir . '/' . $this->strFile);
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

		preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^.\\\\/]+?)|))[\\\\/.]*$%m', $this->strFile, $matches);

		if (isset($matches[1]))
		{
			$return['dirname'] = $this->strRootDir . '/' . $matches[1]; // see #8325
		}

		if (isset($matches[2]))
		{
			$return['basename'] = $matches[2];
		}

		if (isset($matches[5]))
		{
			$return['extension'] = $matches[5];
		}

		if (isset($matches[3]))
		{
			$return['filename'] = $matches[3];
		}

		return $return;
	}
}
