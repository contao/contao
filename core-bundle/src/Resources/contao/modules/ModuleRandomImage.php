<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Model\Collection;

/**
 * Front end module "random image".
 */
class ModuleRandomImage extends Module
{
	/**
	 * Files object
	 * @var Collection|FilesModel
	 */
	protected $objFiles;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_randomImage';

	/**
	 * Check the source folder
	 *
	 * @return string
	 */
	public function generate()
	{
		$this->multiSRC = StringUtil::deserialize($this->multiSRC);

		if (empty($this->multiSRC) || !\is_array($this->multiSRC))
		{
			return '';
		}

		$this->objFiles = FilesModel::findMultipleByUuids($this->multiSRC);

		if ($this->objFiles === null)
		{
			return '';
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$images = array();
		$objFiles = $this->objFiles;

		// Get all images
		while ($objFiles->next())
		{
			// Continue if the files has been processed or does not exist
			if (isset($images[$objFiles->path]) || !file_exists(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFiles->path))
			{
				continue;
			}

			// Single files
			if ($objFiles->type == 'file')
			{
				$objFile = new File($objFiles->path);

				if (!$objFile->isImage)
				{
					continue;
				}

				// Add the image
				$images[$objFiles->path] = $objFiles->current();
			}

			// Folders
			else
			{
				$objSubfiles = FilesModel::findByPid($objFiles->uuid, array('order' => 'name'));

				if ($objSubfiles === null)
				{
					continue;
				}

				while ($objSubfiles->next())
				{
					// Skip subfolders
					if ($objSubfiles->type == 'folder')
					{
						continue;
					}

					$objFile = new File($objSubfiles->path);

					if (!$objFile->isImage)
					{
						continue;
					}

					// Add the image
					$images[$objSubfiles->path] = $objSubfiles->current();
				}
			}
		}

		if (empty($images))
		{
			return;
		}

		$figure = System::getContainer()
			->get('contao.image.studio')
			->createFigureBuilder()
			->fromFilesModel($images[array_rand($images)])
			->setSize($this->imgSize)
			->enableLightbox((bool) $this->fullsize)
			->build();

		$imageData = $figure->getLegacyTemplateData();
		$imageData['figure'] = $figure;

		$this->Template->setData(array_merge(
			$this->Template->getData(),
			$imageData,
			array('caption' => $this->useCaption ? $imageData['title'] ?? '' : null)
		));
	}
}
