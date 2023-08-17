<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Image\DeferredImageInterface;
use Contao\Image\Image as NewImage;
use Contao\Image\ImageDimensions;
use Contao\Image\ImportantPart;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Imagine\Image\Box;

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
 */
class Image
{
	/**
	 * The File instance of the original image
	 *
	 * @var File
	 */
	protected $fileObj;

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
	 * The resize mode (defaults to crop for backwards compatibility)
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
	 * Root dir
	 * @var string
	 */
	protected $strRootDir;

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
	 * @throws \InvalidArgumentException If the file does not exist or cannot be processed
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use the contao.image.factory service instead.
	 */
	public function __construct(File $file)
	{
		trigger_deprecation('contao/core-bundle', '4.3', 'Using the "Contao\Image" class has been deprecated and will no longer work in Contao 5.0. Use the "contao.image.factory" service instead.');

		// Create deferred images (see #5873)
		$file->createIfDeferred();

		// Check whether the file exists
		if (!$file->exists())
		{
			$webDir = System::getContainer()->getParameter('contao.web_dir');

			// Handle public bundle resources
			if (file_exists($webDir . '/' . $file->path))
			{
				$file = new File(StringUtil::stripRootDir($webDir) . '/' . $file->path);
			}
			else
			{
				throw new \InvalidArgumentException('Image "' . $file->path . '" could not be found');
			}
		}

		$this->fileObj = $file;
		$arrAllowedTypes = System::getContainer()->getParameter('contao.image.valid_extensions');

		// Check the file type
		if (!\in_array($this->fileObj->extension, $arrAllowedTypes))
		{
			throw new \InvalidArgumentException('Image type "' . $this->fileObj->extension . '" was not allowed to be processed');
		}

		$this->strRootDir = System::getContainer()->getParameter('kernel.project_dir');
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
			if (!isset($importantPart['x'], $importantPart['y'], $importantPart['width'], $importantPart['height']))
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
		$webDir = StringUtil::stripRootDir(System::getContainer()->getParameter('contao.web_dir'));

		// Strip the contao.web_dir directory prefix (see #337)
		if (strncmp($path, $webDir . '/', \strlen($webDir) + 1) === 0)
		{
			$path = substr($path, \strlen($webDir) + 1);
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

		return StringUtil::stripRootDir(System::getContainer()->getParameter('contao.image.target_dir')) . '/' . substr($strCacheKey, -1) . '/' . $this->fileObj->filename . '-' . $strCacheKey . '.' . $this->fileObj->extension;
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

		if (
			$this->getTargetPath()
			&& !$this->getForceOverride()
			&& !System::getContainer()->getParameter('contao.image.bypass_cache')
			&& file_exists($this->strRootDir . '/' . $this->getTargetPath())
			&& $this->fileObj->mtime <= filemtime($this->strRootDir . '/' . $this->getTargetPath())
		) {
			// HOOK: add custom logic
			if (isset($GLOBALS['TL_HOOKS']['executeResize']) && \is_array($GLOBALS['TL_HOOKS']['executeResize']))
			{
				foreach ($GLOBALS['TL_HOOKS']['executeResize'] as $callback)
				{
					$return = System::importStatic($callback[0])->{$callback[1]}($this);

					if (\is_string($return))
					{
						$this->resizedPath = System::urlEncode($return);

						return $this;
					}
				}
			}

			$this->resizedPath = System::urlEncode($this->getTargetPath());

			return $this;
		}

		$image = System::getContainer()
			->get('contao.image.legacy_resizer')
			->resize(
				$image,
				$resizeConfig,
				(new ResizeOptions())
					->setImagineOptions(System::getContainer()->getParameter('contao.image.imagine_options'))
					->setTargetPath($this->targetPath ? $this->strRootDir . '/' . $this->targetPath : null)
					->setBypassCache(System::getContainer()->getParameter('contao.image.bypass_cache'))
					->setSkipIfDimensionsMatch(true)
			)
		;

		$this->resizedPath = $image->getUrl($this->strRootDir);

		return $this;
	}

	/**
	 * Prepare image object.
	 *
	 * @return NewImage
	 */
	protected function prepareImage()
	{
		if ($this->fileObj->isSvgImage)
		{
			$imagine = System::getContainer()->get('contao.image.imagine_svg');
		}
		else
		{
			$imagine = System::getContainer()->get('contao.image.imagine');
		}

		$image = new NewImage($this->strRootDir . '/' . $this->fileObj->path, $imagine, System::getContainer()->get('filesystem'));
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
			$importantPart['x'] / $this->fileObj->viewWidth,
			$importantPart['y'] / $this->fileObj->viewHeight,
			$importantPart['width'] / $this->fileObj->viewWidth,
			$importantPart['height'] / $this->fileObj->viewHeight
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
			catch (\Throwable $exception)
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
		$resizeCoordinates = System::getContainer()
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
		if (!$src)
		{
			return '';
		}

		$src = rawurldecode($src);

		if (strpos($src, '/') !== false)
		{
			return $src;
		}

		$projectDir = System::getContainer()->getParameter('kernel.project_dir');

		if (strncmp($src, 'icon', 4) === 0)
		{
			if (pathinfo($src, PATHINFO_EXTENSION) == 'svg')
			{
				return 'assets/contao/images/' . $src;
			}

			$filename = pathinfo($src, PATHINFO_FILENAME);

			// Prefer SVG icons
			if (file_exists($projectDir . '/assets/contao/images/' . $filename . '.svg'))
			{
				return 'assets/contao/images/' . $filename . '.svg';
			}

			return 'assets/contao/images/' . $src;
		}

		$theme = Backend::getTheme();

		if (pathinfo($src, PATHINFO_EXTENSION) == 'svg')
		{
			return 'system/themes/' . $theme . '/icons/' . $src;
		}

		$filename = pathinfo($src, PATHINFO_FILENAME);

		// Prefer SVG icons
		if (file_exists($projectDir . '/system/themes/' . $theme . '/icons/' . $filename . '.svg'))
		{
			return 'system/themes/' . $theme . '/icons/' . $filename . '.svg';
		}

		return 'system/themes/' . $theme . '/images/' . $src;
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

		if (!$src)
		{
			return '';
		}

		$container = System::getContainer();
		$projectDir = $container->getParameter('kernel.project_dir');
		$webDir = StringUtil::stripRootDir($container->getParameter('contao.web_dir'));

		if (!is_file($projectDir . '/' . $src))
		{
			try
			{
				$deferredImage = $container->get('contao.image.factory')->create($projectDir . '/' . $src);
			}
			catch (\Exception $e)
			{
				$deferredImage = null;
			}

			// Handle public bundle resources
			if (file_exists($projectDir . '/' . $webDir . '/' . $src))
			{
				$src = $webDir . '/' . $src;
			}
			elseif (!$deferredImage instanceof DeferredImageInterface)
			{
				return '';
			}
		}

		$objFile = new File($src);

		// Strip the contao.web_dir directory prefix (see #337)
		if (strncmp($src, $webDir . '/', \strlen($webDir) + 1) === 0)
		{
			$src = substr($src, \strlen($webDir) + 1);
		}

		$context = (strncmp($src, 'assets/', 7) === 0) ? 'assets_context' : 'files_context';

		return '<img src="' . Controller::addStaticUrlTo(System::urlEncode($src), $container->get('contao.assets.' . $context)) . '" width="' . $objFile->width . '" height="' . $objFile->height . '" alt="' . StringUtil::specialchars($alt) . '"' . ($attributes ? ' ' . $attributes : '') . '>';
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
	 *             Use the contao.image.factory service instead.
	 */
	public static function resize($image, $width, $height, $mode='')
	{
		trigger_deprecation('contao/core-bundle', '4.3', 'Using "Contao\Image::resize()" has been deprecated and will no longer work in Contao 5.0. Use the "contao.image.factory" service instead.');

		return static::get($image, $width, $height, $mode, $image, true) ? true : false;
	}

	/**
	 * Create an image instance from the given image path and size
	 *
	 * @param string|File          $image The image path or File instance
	 * @param array|integer|string $size  The image size as array (width, height, resize mode) or a tl_image_size ID or a predefined image size key
	 *
	 * @return static The created image instance
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use the contao.image.factory service instead.
	 */
	public static function create($image, $size=null)
	{
		trigger_deprecation('contao/core-bundle', '4.3', 'Using "Contao\Image::create()" has been deprecated and will no longer work in Contao 5.0. Use the "contao.image.factory" service instead.');

		if (\is_string($image))
		{
			$image = new File(rawurldecode($image));
		}

		$imageObj = new static($image);

		if (\is_array($size) && !empty($size[2]))
		{
			// tl_image_size ID as resize mode
			if (is_numeric($size[2]))
			{
				$size = (int) $size[2];
			}

			// Predefined image size as resize mode
			elseif (\is_string($size[2]) && $size[2][0] === '_')
			{
				$size = $size[2];
			}
		}

		if (\is_array($size))
		{
			$size += array(0, 0, 'crop');

			$imageObj
				->setTargetWidth($size[0])
				->setTargetHeight($size[1])
				->setResizeMode($size[2])
			;
		}

		// Load the image size from the database if $size is an ID or a predefined size
		elseif (($imageSize = self::getImageSizeConfig($size)) !== null)
		{
			$imageObj
				->setTargetWidth($imageSize->width)
				->setTargetHeight($imageSize->height)
				->setResizeMode($imageSize->resizeMode)
				->setZoomLevel($imageSize->zoom)
			;
		}

		$fileRecord = FilesModel::findByPath($image->path);
		$currentSize = $image->imageViewSize;

		// Set the important part
		if ($fileRecord !== null && $fileRecord->importantPartWidth && $fileRecord->importantPartHeight)
		{
			$imageObj->setImportantPart(array
			(
				'x' => (int) ($fileRecord->importantPartX * $currentSize[0]),
				'y' => (int) ($fileRecord->importantPartY * $currentSize[1]),
				'width' => (int) ($fileRecord->importantPartWidth * $currentSize[0]),
				'height' => (int) ($fileRecord->importantPartHeight * $currentSize[1]),
			));
		}

		return $imageObj;
	}

	private static function getImageSizeConfig($size)
	{
		if (is_numeric($size))
		{
			return ImageSizeModel::findByPk($size);
		}

		if (!\is_string($size) || $size[0] !== '_')
		{
			return null;
		}

		static $predefinedSizes = null;

		if ($predefinedSizes === null)
		{
			$factory = System::getContainer()->get('contao.image.factory');
			$predefinedSizes = (new \ReflectionObject($factory))->getProperty('predefinedSizes');
			$predefinedSizes->setAccessible(true);
			$predefinedSizes = $predefinedSizes->getValue($factory) ?? array();
		}

		if (!isset($predefinedSizes[$size]))
		{
			return null;
		}

		$imageSize = new \stdClass();
		$imageSize->width = $predefinedSizes[$size]['width'] ?? 0;
		$imageSize->height = $predefinedSizes[$size]['height'] ?? 0;
		$imageSize->resizeMode = $predefinedSizes[$size]['resizeMode'] ?? 'proportional';
		$imageSize->zoom = $predefinedSizes[$size]['zoom'] ?? 0;

		return $imageSize;
	}

	/**
	 * Resize an image and store the resized version in the image target folder
	 *
	 * @param string  $image  The image path
	 * @param integer $width  The target width
	 * @param integer $height The target height
	 * @param string  $mode   The resize mode
	 * @param string  $target An optional target path
	 * @param boolean $force  Override existing target images
	 *
	 * @return string|null The path of the resized image or null
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use the contao.image.factory service instead.
	 */
	public static function get($image, $width, $height, $mode='', $target=null, $force=false)
	{
		trigger_deprecation('contao/core-bundle', '4.3', 'Using "Contao\Image::get()" has been deprecated and will no longer work in Contao 5.0. Use the "contao.image.factory" service instead.');

		if (!$image)
		{
			return null;
		}

		try
		{
			$imageObj = static::create($image, array($width, $height, $mode));
			$imageObj->setTargetPath($target);
			$imageObj->setForceOverride($force);

			if ($path = $imageObj->executeResize()->getResizedPath())
			{
				return $path;
			}
		}
		catch (\Exception $e)
		{
			System::getContainer()->get('monolog.logger.contao.error')->error('Image "' . $image . '" could not be processed: ' . $e->getMessage());
			throw $e;
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
	 *             Use the contao.image.factory service instead.
	 */
	public static function getPixelValue($size)
	{
		trigger_deprecation('contao/core-bundle', '4.3', 'Using "Contao\Image::getPixelValue()" has been deprecated and will no longer work in Contao 5.0. Use the "contao.image.factory" service instead.');

		$value = preg_replace('/[^0-9.-]+/', '', $size);
		$unit = preg_replace('/[^acehimnprtvwx%]/', '', $size);

		// Convert 16px = 1em = 2ex = 12pt = 1pc = 1/6in = 2.54/6cm = 25.4/6mm = 100%
		switch ($unit)
		{
			case '':
			case 'px':
				return (int) round($value);

			case 'pc':
			case 'em':
				return (int) round($value * 16);

			case 'ex':
				return (int) round($value * 16 / 2);

			case 'pt':
				return (int) round($value * 16 / 12);

			case 'in':
				return (int) round($value * 16 * 6);

			case 'cm':
				return (int) round($value * 16 / (2.54 / 6));

			case 'mm':
				return (int) round($value * 16 / (25.4 / 6));

			case '%':
				return (int) round($value * 16 / 100);
		}

		return 0;
	}
}

class_alias(Image::class, 'Image');
