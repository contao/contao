<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Image\ImportantPart;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\Model\Collection;

trigger_deprecation('contao/core-bundle', '4.3', 'Using the "Contao\Picture" class has been deprecated and will no longer work in Contao 5.0. Use the "contao.image.picture_factory" service instead.');

/**
 * Resizes images and creates picture data
 *
 * The class resizes images and prepares data for the `<picture>` element.
 *
 * Usage:
 *
 *     $picture = new Picture(new File('example.jpg'));
 *
 *     $data = $picture
 *         ->setImportantPart(array('x'=>10, 'y'=>10, 'width'=>100, 'height'=>100))
 *         ->setImageSize(ImageSizeModel::findByPk(1))
 *         ->setImageSizeItems(ImageSizeItemModel::findVisibleByPid(1, array('order'=>'sorting ASC')))
 *         ->getTemplateData()
 *     ;
 *
 *     // Shortcut
 *     $data = Picture::create('example.jpg', 1)->getTemplateData();
 *     $data = Picture::create('example.jpg', array(100, 100, 'crop'))->getTemplateData();
 *
 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
 *             Use the contao.image.picture_factory service instead.
 */
class Picture
{
	/**
	 * The Image instance of the source image
	 *
	 * @var Image
	 */
	protected $image;

	/**
	 * The image size
	 *
	 * @var ImageSizeModel|object
	 */
	protected $imageSize;

	/**
	 * The image size items collection
	 *
	 * @var array<ImageSizeItemModel|object>|Collection
	 */
	protected $imageSizeItems = array();

	/**
	 * Create a new object to handle a picture element
	 *
	 * @param File $file A file instance of the source image
	 */
	public function __construct(File $file)
	{
		$this->image = new Image($file);
	}

	/**
	 * Create a picture instance from the given image path and size
	 *
	 * @param string|File          $file The image path or File instance
	 * @param array|integer|string $size The image size as array (width, height, resize mode) or a tl_image_size ID or a predefined image size key
	 *
	 * @return static The created picture instance
	 */
	public static function create($file, $size=null)
	{
		if (\is_string($file))
		{
			$file = new File(rawurldecode($file));
		}

		$picture = new static($file);

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

		$imageSize = null;

		if (!\is_array($size) && (!\is_string($size) || $size[0] !== '_'))
		{
			$imageSize = ImageSizeModel::findByPk($size);

			if ($imageSize === null)
			{
				$size = array();
			}
		}

		if (\is_string($size) && $size[0] === '_')
		{
			$imageSize = $size;
		}

		if (\is_array($size))
		{
			$size += array(0, 0, 'crop');

			$imageSize = new \stdClass();
			$imageSize->width = $size[0];
			$imageSize->height = $size[1];
			$imageSize->resizeMode = $size[2];
			$imageSize->zoom = 0;
		}

		$picture->setImageSize($imageSize);

		if ($imageSize !== null && !empty($imageSize->id))
		{
			$picture->setImageSizeItems(ImageSizeItemModel::findVisibleByPid($imageSize->id, array('order'=>'sorting ASC')));
		}

		$fileRecord = FilesModel::findByPath($file->path);
		$currentSize = $file->imageViewSize;

		if ($fileRecord !== null && $fileRecord->importantPartWidth && $fileRecord->importantPartHeight)
		{
			$picture->setImportantPart(array
			(
				'x' => (int) ($fileRecord->importantPartX * $currentSize[0]),
				'y' => (int) ($fileRecord->importantPartY * $currentSize[1]),
				'width' => (int) ($fileRecord->importantPartWidth * $currentSize[0]),
				'height' => (int) ($fileRecord->importantPartHeight * $currentSize[1]),
			));
		}

		return $picture;
	}

	/**
	 * Set the important part settings
	 *
	 * @param array $importantPart The settings array
	 *
	 * @return $this The picture object
	 */
	public function setImportantPart(array $importantPart = null)
	{
		$this->image->setImportantPart($importantPart);

		return $this;
	}

	/**
	 * Set the image size
	 *
	 * @param ImageSizeModel|object|string $imageSize The image size or a predefined image size key
	 *
	 * @return $this The picture object
	 */
	public function setImageSize($imageSize)
	{
		$this->imageSize = $imageSize;

		return $this;
	}

	/**
	 * Set the image size items collection
	 *
	 * @param array<ImageSizeItemModel|object>|Collection $imageSizeItems The image size items collection
	 *
	 * @return $this The picture object
	 */
	public function setImageSizeItems($imageSizeItems)
	{
		if ($imageSizeItems === null)
		{
			$imageSizeItems = array();
		}

		$this->imageSizeItems = $imageSizeItems;

		return $this;
	}

	/**
	 * Get the picture element definition array
	 *
	 * @return array The picture element definition
	 */
	public function getTemplateData()
	{
		$projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$image = System::getContainer()->get('contao.image.factory')->create($projectDir . '/' . $this->image->getOriginalPath());

		if (\is_string($this->imageSize) && $this->imageSize[0] === '_')
		{
			$config = $this->imageSize;
		}
		else
		{
			$config = new PictureConfiguration();
			$config->setSize($this->getConfigurationItem($this->imageSize));

			$sizeItems = array();

			foreach ($this->imageSizeItems as $imageSizeItem)
			{
				$sizeItems[] = $this->getConfigurationItem($imageSizeItem);
			}

			$config->setSizeItems($sizeItems);
		}

		$importantPart = $this->image->getImportantPart();
		$imageSize = $image->getDimensions()->getSize();

		$image->setImportantPart(
			new ImportantPart(
				$importantPart['x'] / $imageSize->getWidth(),
				$importantPart['y'] / $imageSize->getHeight(),
				$importantPart['width'] / $imageSize->getWidth(),
				$importantPart['height'] / $imageSize->getHeight()
			)
		);

		$container = System::getContainer();
		$staticUrl = $container->get('contao.assets.files_context')->getStaticUrl();

		$picture = $container
			->get('contao.image.picture_factory')
			->create(
				$image,
				$config,
				(new ResizeOptions())
					->setImagineOptions($container->getParameter('contao.image.imagine_options'))
					->setBypassCache($container->getParameter('contao.image.bypass_cache'))
					->setSkipIfDimensionsMatch(true)
			)
		;

		return array
		(
			'img' => $picture->getImg($projectDir, $staticUrl),
			'sources' => $picture->getSources($projectDir, $staticUrl),
		);
	}

	/**
	 * Get the config for one picture source element
	 *
	 * @param ImageSizeModel|ImageSizeItemModel|object $imageSize The image size or image size item model
	 *
	 * @return PictureConfigurationItem
	 */
	protected function getConfigurationItem($imageSize)
	{
		$configItem = new PictureConfigurationItem();
		$resizeConfig = new ResizeConfiguration();

		$mode = $imageSize->resizeMode;

		if (substr_count($mode, '_') === 1)
		{
			$importantPart = $this->image->setImportantPart()->getImportantPart();

			$mode = explode('_', $mode);

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

			$this->image->setImportantPart($importantPart);

			$mode = ResizeConfiguration::MODE_CROP;
		}

		$resizeConfig
			->setWidth((int) $imageSize->width)
			->setHeight((int) $imageSize->height)
			->setZoomLevel((int) $imageSize->zoom)
		;

		if ($mode)
		{
			$resizeConfig->setMode($mode);
		}

		$configItem->setResizeConfig($resizeConfig);

		if (isset($imageSize->sizes))
		{
			$configItem->setSizes($imageSize->sizes);
		}

		if (isset($imageSize->densities))
		{
			$configItem->setDensities($imageSize->densities);
		}

		if (isset($imageSize->media))
		{
			$configItem->setMedia($imageSize->media);
		}

		return $configItem;
	}
}

class_alias(Picture::class, 'Picture');
