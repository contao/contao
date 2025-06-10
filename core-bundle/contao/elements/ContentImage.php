<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\ContentElement\ImagesController;

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class has been deprecated and will no longer work in Contao 6. Use the "%s" class instead.', ContentImage::class, ImagesController::class);

/**
 * Front end content element "image".
 */
class ContentImage extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_image';

	/**
	 * Files model
	 * @var FilesModel
	 */
	protected $objFilesModel;

	/**
	 * Return if the image does not exist
	 *
	 * @return string
	 */
	public function generate()
	{
		if (!$this->singleSRC)
		{
			return '';
		}

		$objFile = FilesModel::findByUuid($this->singleSRC);

		if ($objFile === null || !is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFile->path))
		{
			return '';
		}

		$this->singleSRC = $objFile->path;
		$this->objFilesModel = $objFile;

		return parent::generate();
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$figure = System::getContainer()
			->get('contao.image.studio')
			->createFigureBuilder()
			->from($this->objFilesModel)
			->setSize($this->size)
			->setOverwriteMetadata($this->objModel->getOverwriteMetadata())
			->enableLightbox($this->fullsize)
			->buildIfResourceExists();

		$figure?->applyLegacyTemplateData($this->Template);
	}
}
