<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

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
			->setMetadata($this->objModel->getOverwriteMetadata())
			->enableLightbox((bool) $this->fullsize)
			->buildIfResourceExists();

		if (null !== $figure)
		{
			$figure->applyLegacyTemplateData($this->Template);
		}
	}
}
