<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Image\Studio\LegacyFigureBuilderTrait;

/**
 * Front end content element "image".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentImage extends ContentElement
{
	use LegacyFigureBuilderTrait;

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
		$figureBuilder = $this->getFigureBuilderIfResourceExists($this->objFilesModel);

		if (null === $figureBuilder)
		{
			return;
		}

		$figureBuilder
			->setSize($this->size)
			->setMetadata($this->objModel->getOverwriteMetadata())
			->enableLightbox((bool) $this->fullsize)
			->build()
			->applyLegacyTemplateData($this->Template, $this->imagemargin);
	}
}

class_alias(ContentImage::class, 'ContentImage');
