<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\Image\Image as NewImage;
use Contao\Image\ImportantPart;
use Contao\Image\ImageDimensions;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Imagine\Image\Box;
use Imagine\Image\Point;


/**
 * Resizes images
 *
 * The class resizes images and stores them in the image target folder.
 *
 * Usage:
 *
 *     $imageObj = new Image(new File('example.jpg'));
 *
 *     $src = $imageObj->setTargetWidth(640)
 *                     ->setTargetHeight(480)
 *                     ->setResizeMode('center_center')
 *                     ->executeResize()
 *                     ->getResizedPath();
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Martin Auswöger <https://github.com/ausi>
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class Image
{

	/**
	 * The File instance of the original image
	 *
	 * @var File
	 */
	protected $fileObj = null;

	/**
	 * The resized image path
	 *
	 * @var string
	 */
	protected $resizedPath = '';

	/**
	 * The target width
	 *
	 * @var integer
	 */
	protected $targetWidth = 0;

	/**
	 * The target height
	 *
	 * @var integer
	 */
	protected $targetHeight = 0;

	/**
	 * The resize mode (defaults to crop for BC)
	 *
	 * @var string
	 */
	protected $resizeMode = 'crop';

	/**
	 * The target path
	 *
	 * @var string
	 */
	protected $targetPath = '';

	/**
	 * Override an existing target
	 *
	 * @var boolean
	 */
	protected $forceOverride = false;

	/**
	 * Zoom level (between 0 and 100)
	 *
	 * @var integer
	 */
	protected $zoomLevel = 0;

	/**
	 * Important part settings
	 *
	 * @var array
	 */
	protected $importantPart = array();


	/**
	 * Create a new object to handle an image
	 *
	 * @param File $file A file instance of the original image
	 *
	 * @throws \InvalidArgumentException If the file does not exists or cannot be processed
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use the contao.image.image_factory service instead.
	 */
	public function __construct(File $file)
	{
		@trigger_error('Using new Contao\Image() has been deprecated and will no longer work in Contao 5.0. Use the contao.image.image_factory service instead.', E_USER_DEPRECATED);

		// Check whether the file exists
		if (!$file->exists())
		{
			// Handle public bundle resources
			if (file_exists(TL_ROOT . '/web/' . $file->path))
			{
				$file = new \File('web/' . $file->path);
			}
			else
			{
				throw new \InvalidArgumentException('Image "' . $file->path . '" could not be found');
			}
		}

		$this->fileObj = $file;
		$arrAllowedTypes = \StringUtil::trimsplit(',', strtolower(\Config::get('validImageTypes')));

		// Check the file type
		if (!in_array($this->fileObj->extension, $arrAllowedTypes))
		{
			throw new \InvalidArgumentException('Image type "' . $this->fileObj->extension . '" was not allowed to be processed');
		}
	}


	/**
	 * Override the target image
	 *
	 * @param boolean $forceOverride True to override the target image
	 *
	 * @return $this The image object
	 */
	public function setForceOverride($forceOverride)
	{
		$this->forceOverride = (bool) $forceOverride;

		return $this;
	}


	/**
	 * Get force override setting
	 *
	 * @return boolean True if the target image will be overridden
	 */
	public function getForceOverride()
	{
		return $this->forceOverride;
	}


	/**
	 * Set the important part settings
	 *
	 * @param array $importantPart The settings array
	 *
	 * @return $this The image object
	 *
	 * @throws \InvalidArgumentException If the settings array is malformed
	 */
	public function setImportantPart(array $importantPart = null)
	{
		if ($importantPart !== null)
		{
			if (!isset($importantPart['x']) || !isset($importantPart['y']) || !isset($importantPart['width']) || !isset($importantPart['height']))
			{
				throw new \InvalidArgumentException('Malformed array for setting the important part!');
			}

			$this->importantPart = array
			(
				'x' => max(0, min($this->fileObj->viewWidth - 1, (int) $importantPart['x'])),
				'y' => max(0, min($this->fileObj->viewHeight - 1, (int) $importantPart['y'])),
			);

			$this->importantPart['width'] = max(1, min($this->fileObj->viewWidth - $this->importantPart['x'], (int) $importantPart['width']));
			$this->importantPart['height'] = max(1, min($this->fileObj->viewHeight - $this->importantPart['y'], (int) $importantPart['height']));

		}
		else
		{
			$this->importantPart = null;
		}

		return $this;
	}


	/**
	 * Get the important part settings
	 *
	 * @return array The settings array
	 */
	public function getImportantPart()
	{
		if ($this->importantPart)
		{
			return $this->importantPart;
		}

		return array('x'=>0, 'y'=>0, 'width'=>$this->fileObj->viewWidth, 'height'=>$this->fileObj->viewHeight);
	}


	/**
	 * Set the target height
	 *
	 * @param integer $targetHeight The target height
	 *
	 * @return $this The image object
	 */
	public function setTargetHeight($targetHeight)
	{
		$this->targetHeight = (int) $targetHeight;

		return $this;
	}


	/**
	 * Get the target height
	 *
	 * @return integer The target height
	 */
	public function getTargetHeight()
	{
		return $this->targetHeight;
	}


	/**
	 * Set the target width
	 *
	 * @param integer $targetWidth The target width
	 *
	 * @return $this The image object
	 */
	public function setTargetWidth($targetWidth)
	{
		$this->targetWidth = (int) $targetWidth;

		return $this;
	}


	/**
	 * Get the target width
	 *
	 * @return integer The target width
	 */
	public function getTargetWidth()
	{
		return $this->targetWidth;
	}


	/**
	 * Set the target path
	 *
	 * @param string $targetPath The target path
	 *
	 * @return $this The image object
	 */
	public function setTargetPath($targetPath)
	{
		$this->targetPath = (string) $targetPath;

		return $this;
	}


	/**
	 * Get the target path
	 *
	 * @return string The target path
	 */
	public function getTargetPath()
	{
		return $this->targetPath;
	}


	/**
	 * Set the zoom level
	 *
	 * @param integer $zoomLevel The zoom level
	 *
	 * @return $this The object instance
	 *
	 * @throws \InvalidArgumentException If the zoom level is out of bounds
	 */
	public function setZoomLevel($zoomLevel)
	{
		$zoomLevel = (int) $zoomLevel;

		if ($zoomLevel < 0 || $zoomLevel > 100)
		{
			throw new \InvalidArgumentException('Zoom level must be between 0 and 100!');
		}

		$this->zoomLevel = $zoomLevel;

		return $this;
	}


	/**
	 * Get the zoom level
	 *
	 * @return integer The zoom level
	 */
	public function getZoomLevel()
	{
		return $this->zoomLevel;
	}


	/**
	 * Set the resize mode
	 *
	 * @param string $resizeMode The resize mode
	 *
	 * @return $this The image object
	 */
	public function setResizeMode($resizeMode)
	{
		$this->resizeMode = $resizeMode;

		return $this;
	}


	/**
	 * Get the resize mode
	 *
	 * @return string The resize mode
	 */
	public function getResizeMode()
	{
		return $this->resizeMode;
	}


	/**
	 * Get the path of the original image
	 *
	 * @return string The path of the original image
	 */
	public function getOriginalPath()
	{
		return $this->fileObj->path;
	}


	/**
	 * Get the path of the resized image
	 *
	 * @return string The path of the resized image
	 */
	public function getResizedPath()
	{
		$path = $this->resizedPath;

		// Strip the web/ prefix (see #337)
		if (strncmp($path, 'web/', 4) === 0)
		{
			$path = substr($path, 4);
		}

		return $path;
	}


	/**
	 * Get the cache name
	 *
	 * @return string The cache name
	 */
	public function getCacheName()
	{
		$importantPart = $this->getImportantPart();

		$strCacheKey = substr(md5
		(
			  '-w' . $this->getTargetWidth()
			. '-h' . $this->getTargetHeight()
			. '-o' . $this->getOriginalPath()
			. '-m' . $this->getResizeMode()
			. '-z' . $this->getZoomLevel()
			. '-x' . $importantPart['x']
			. '-y' . $importantPart['y']
			. '-i' . $importantPart['width']
			. '-e' . $importantPart['height']
			. '-t' . $this->fileObj->mtime
		), 0, 8);

		return System::getContainer()->getParameter('contao.image.target_path') . '/' . substr($strCacheKey, -1) . '/' . $this->fileObj->filename . '-' . $strCacheKey . '.' . $this->fileObj->extension;
	}


	/**
	 * Resize the image
	 *
	 * @return $this The image object
	 */
	public function executeResize()
	{
		$image = $this->prepareImage();
		$resizeConfig = $this->prepareResizeConfig();

		if (!System::getContainer()->getParameter('contao.image.bypass_cache')
			&& $this->getTargetPath()
			&& !$this->getForceOverride()
			&& file_exists(TL_ROOT . '/' . $this->getTargetPath())
			&& $this->fileObj->mtime <= filemtime(TL_ROOT . '/' . $this->getTargetPath())
		) {
			// HOOK: add custom logic
			if (isset($GLOBALS['TL_HOOKS']['executeResize']) && is_array($GLOBALS['TL_HOOKS']['executeResize']))
			{
				foreach ($GLOBALS['TL_HOOKS']['executeResize'] as $callback)
				{
					$return = \System::importStatic($callback[0])->{$callback[1]}($this);

					if (is_string($return))
					{
						$this->resizedPath = \System::urlEncode($return);

						return $this;
					}
				}
			}

			$this->resizedPath = \System::urlEncode($this->getTargetPath());

			return $this;
		}

		$image = \System::getContainer()
			->get('contao.image.resizer')
			->resize(
				$image,
				$resizeConfig,
				(new ResizeOptions())
					->setImagineOptions(\System::getContainer()->getParameter('contao.image.imagine_options'))
					->setTargetPath($this->targetPath ? TL_ROOT . '/' . $this->targetPath : null)
					->setBypassCache(\System::getContainer()->getParameter('contao.image.bypass_cache'))
			)
		;

		$this->resizedPath = $image->getPath();

		if (strpos($this->resizedPath, TL_ROOT . '/') === 0 || strpos($this->resizedPath, TL_ROOT . '\\') === 0)
		{
			$this->resizedPath = substr($this->resizedPath, strlen(TL_ROOT) + 1);
		}

		$this->resizedPath = \System::urlEncode($this->resizedPath);

		return $this;
	}


	/**
	 * Prepare image object.
	 *
	 * @return \Contao\Image\Image
	 */
	protected function prepareImage()
	{
		if ($this->fileObj->isSvgImage)
		{
			$imagine = \System::getContainer()->get('contao.image.imagine_svg');
		}
		else
		{
			$imagine = \System::getContainer()->get('contao.image.imagine');
		}

		$image = new NewImage(TL_ROOT . '/' . $this->fileObj->path, $imagine, \System::getContainer()->get('filesystem'));
		$image->setImportantPart($this->prepareImportantPart());

		return $image;
	}


	/**
	 * Prepare important part object.
	 *
	 * @return ImportantPart
	 */
	protected function prepareImportantPart()
	{
		$importantPart = $this->getImportantPart();

		if (substr_count($this->resizeMode, '_') === 1)
		{
			$importantPart = array
			(
				'x' => 0,
				'y' => 0,
				'width' => $this->fileObj->viewWidth,
				'height' => $this->fileObj->viewHeight,
			);

			$mode = explode('_', $this->resizeMode);

			if ($mode[0] === 'left')
			{
				$importantPart['width'] = 1;
			}
			elseif ($mode[0] === 'right')
			{
				$importantPart['x'] = $importantPart['width'] - 1;
				$importantPart['width'] = 1;
			}

			if ($mode[1] === 'top')
			{
				$importantPart['height'] = 1;
			}
			elseif ($mode[1] === 'bottom')
			{
				$importantPart['y'] = $importantPart['height'] - 1;
				$importantPart['height'] = 1;
			}
		}

		if (!$importantPart['width'] || !$importantPart['height'])
		{
			return null;
		}

		return new ImportantPart(
			new Point($importantPart['x'], $importantPart['y']),
			new Box($importantPart['width'], $importantPart['height'])
		);
	}


	/**
	 * Prepare resize configuration object.
	 *
	 * @return ResizeConfiguration
	 */
	protected function prepareResizeConfig()
	{
		$resizeConfig = new ResizeConfiguration();
		$resizeConfig->setWidth($this->targetWidth);
		$resizeConfig->setHeight($this->targetHeight);
		$resizeConfig->setZoomLevel($this->zoomLevel);

		if (substr_count($this->resizeMode, '_') === 1)
		{
			$resizeConfig->setMode(ResizeConfiguration::MODE_CROP);
			$resizeConfig->setZoomLevel(0);
		}
		else
		{
			try
			{
				$resizeConfig->setMode($this->resizeMode);
			}
			catch (\InvalidArgumentException $exception)
			{
				$resizeConfig->setMode(ResizeConfiguration::MODE_CROP);
			}
		}

		return $resizeConfig;
	}


	/**
	 * Calculate the resize coordinates
	 *
	 * @return array The resize coordinates (width, height, target_x, target_y, target_width, target_height)
	 */
	public function computeResize()
	{
		$resizeCoordinates = \System::getContainer()
			->get('contao.image.resize_calculator')
			->calculate(
				$this->prepareResizeConfig(),
				new ImageDimensions(
					new Box($this->fileObj->viewWidth, $this->fileObj->viewHeight),
					$this->fileObj->viewWidth !== $this->fileObj->width
				),
				$this->prepareImportantPart()
			)
		;

		return array
		(
			'width' => $resizeCoordinates->getCropSize()->getWidth(),
			'height' => $resizeCoordinates->getCropSize()->getHeight(),
			'target_x' => -$resizeCoordinates->getCropStart()->getX(),
			'target_y' => -$resizeCoordinates->getCropStart()->getY(),
			'target_width' => $resizeCoordinates->getSize()->getWidth(),
			'target_height' => $resizeCoordinates->getSize()->getHeight(),
		);
	}


	/**
	 * Get the relative path to an image
	 *
	 * @param string $src The image name or path
	 *
	 * @return string The relative path
	 */
	public static function getPath($src)
	{
		if ($src == '')
		{
			return '';
		}

		$src = rawurldecode($src);

		if (strpos($src, '/') !== false)
		{
			return $src;
		}

		if (strncmp($src, 'icon', 4) === 0)
		{
			if (pathinfo($src, PATHINFO_EXTENSION) == 'svg')
			{
				return 'assets/contao/images/' . $src;
			}

			$filename = pathinfo($src, PATHINFO_FILENAME);

			// Prefer SVG icons
			if (file_exists(TL_ROOT . '/assets/contao/images/' . $filename . '.svg'))
			{
				return 'assets/contao/images/' . $filename . '.svg';
			}

			return 'assets/contao/images/' . $src;
		}
		else
		{
			$theme = \Backend::getTheme();

			if (pathinfo($src, PATHINFO_EXTENSION) == 'svg')
			{
				return 'system/themes/' . $theme . '/icons/' . $src;
			}

			$filename = pathinfo($src, PATHINFO_FILENAME);

			// Prefer SVG icons
			if (file_exists(TL_ROOT . '/system/themes/' . $theme . '/icons/' . $filename . '.svg'))
			{
				return 'system/themes/' . $theme . '/icons/' . $filename . '.svg';
			}

			return 'system/themes/' . $theme . '/images/' . $src;
		}
	}


	/**
	 * Generate an image tag and return it as string
	 *
	 * @param string $src        The image path
	 * @param string $alt        An optional alt attribute
	 * @param string $attributes A string of other attributes
	 *
	 * @return string The image HTML tag
	 */
	public static function getHtml($src, $alt='', $attributes='')
	{
		$src = static::getPath($src);

		if ($src == '')
		{
			return '';
		}

		if (!is_file(TL_ROOT . '/' . $src))
		{
			// Handle public bundle resources
			if (file_exists(TL_ROOT . '/web/' . $src))
			{
				$src = 'web/' . $src;
			}
			else
			{
				return '';
			}
		}

		$objFile = new \File($src);

		// Strip the web/ prefix (see #337)
		if (strncmp($src, 'web/', 4) === 0)
		{
			$src = substr($src, 4);
		}

		$static = (strncmp($src, 'assets/', 7) === 0) ? TL_ASSETS_URL : TL_FILES_URL;

		return '<img src="' . $static . \System::urlEncode($src) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . \StringUtil::specialchars($alt) . '"' . (($attributes != '') ? ' ' . $attributes : '') . '>';
	}


	/**
	 * Resize or crop an image and replace the original with the resized version
	 *
	 * @param string  $image  The image path
	 * @param integer $width  The target width
	 * @param integer $height The target height
	 * @param string  $mode   The resize mode
	 *
	 * @return boolean True if the image could be resized successfully
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use the contao.image.image_factory service instead.
	 */
	public static function resize($image, $width, $height, $mode='')
	{
		@trigger_error('Using Image::resize() has been deprecated and will no longer work in Contao 5.0. Use the contao.image.image_factory service instead.', E_USER_DEPRECATED);

		return static::get($image, $width, $height, $mode, $image, true) ? true : false;
	}


	/**
	 * Create an image instance from the given image path and size
	 *
	 * @param string|File   $image The image path or File instance
	 * @param array|integer $size  The image size as array (width, height, resize mode) or an tl_image_size ID
	 *
	 * @return static The created image instance
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use the contao.image.image_factory service instead.
	 */
	public static function create($image, $size=null)
	{
		@trigger_error('Using Image::create() has been deprecated and will no longer work in Contao 5.0. Use the contao.image.image_factory service instead.', E_USER_DEPRECATED);

		if (is_string($image))
		{
			$image = new \File(rawurldecode($image));
		}

		/** @var Image $imageObj */
		$imageObj = new static($image);

		// tl_image_size ID as resize mode
		if (is_array($size) && !empty($size[2]) && is_numeric($size[2]))
		{
			$size = (int) $size[2];
		}

		if (is_array($size))
		{
			$size = $size + array(0, 0, 'crop');

			$imageObj
				->setTargetWidth($size[0])
				->setTargetHeight($size[1])
				->setResizeMode($size[2])
			;
		}

		// Load the image size from the database if $size is an ID
		elseif (($imageSize = \ImageSizeModel::findByPk($size)) !== null)
		{
			$imageObj
				->setTargetWidth($imageSize->width)
				->setTargetHeight($imageSize->height)
				->setResizeMode($imageSize->resizeMode)
				->setZoomLevel($imageSize->zoom)
			;
		}

		$fileRecord = \FilesModel::findByPath($image->path);

		// Set the important part
		if ($fileRecord !== null && $fileRecord->importantPartWidth && $fileRecord->importantPartHeight)
		{
			$imageObj->setImportantPart(array
			(
				'x' => (int) $fileRecord->importantPartX,
				'y' => (int) $fileRecord->importantPartY,
				'width' => (int) $fileRecord->importantPartWidth,
				'height' => (int) $fileRecord->importantPartHeight,
			));
		}

		return $imageObj;
	}


	/**
	 * Resize an image and store the resized version in the image target folder
	 *
	 * @param string  $image        The image path
	 * @param integer $width        The target width
	 * @param integer $height       The target height
	 * @param string  $mode         The resize mode
	 * @param string  $target       An optional target path
	 * @param boolean $force        Override existing target images
	 *
	 * @return string|null The path of the resized image or null
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use the contao.image.image_factory service instead.
	 */
	public static function get($image, $width, $height, $mode='', $target=null, $force=false)
	{
		@trigger_error('Using Image::get() has been deprecated and will no longer work in Contao 5.0. Use the contao.image.image_factory service instead.', E_USER_DEPRECATED);

		if ($image == '')
		{
			return null;
		}

		try
		{
			$imageObj = static::create($image, array($width, $height, $mode));
			$imageObj->setTargetPath($target);
			$imageObj->setForceOverride($force);

			if (($path = $imageObj->executeResize()->getResizedPath()) != '')
			{
				return $path;
			}
		}
		catch (\Exception $e)
		{
			\System::log('Image "' . $image . '" could not be processed: ' . $e->getMessage(), __METHOD__, TL_ERROR);
		}

		return null;
	}


	/**
	 * Convert sizes like 2em, 10cm or 12pt to pixels
	 *
	 * @param string $size The size string
	 *
	 * @return integer The pixel value
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use the contao.image.image_factory service instead.
	 */
	public static function getPixelValue($size)
	{
		@trigger_error('Using Image::getPixelValue() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

		$value = preg_replace('/[^0-9.-]+/', '', $size);
		$unit = preg_replace('/[^acehimnprtvwx%]/', '', $size);

		// Convert 16px = 1em = 2ex = 12pt = 1pc = 1/6in = 2.54/6cm = 25.4/6mm = 100%
		switch ($unit)
		{
			case '':
			case 'px':
				return (int) round($value);
				break;

			case 'em':
				return (int) round($value * 16);
				break;

			case 'ex':
				return (int) round($value * 16 / 2);
				break;

			case 'pt':
				return (int) round($value * 16 / 12);
				break;

			case 'pc':
				return (int) round($value * 16);
				break;

			case 'in':
				return (int) round($value * 16 * 6);
				break;

			case 'cm':
				return (int) round($value * 16 / (2.54 / 6));
				break;

			case 'mm':
				return (int) round($value * 16 / (25.4 / 6));
				break;

			case '%':
				return (int) round($value * 16 / 100);
				break;
		}

		return 0;
	}
}
