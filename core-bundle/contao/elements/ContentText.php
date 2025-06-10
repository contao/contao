<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\ContentElement\TextController;

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class has been deprecated and will no longer work in Contao 6. Use the "%s" class instead.', ContentText::class, TextController::class);

/**
 * Front end content element "text".
 */
class ContentText extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_text';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		// Add the static files URL to images
		if ($staticUrl = System::getContainer()->get('contao.assets.files_context')->getStaticUrl())
		{
			$path = System::getContainer()->getParameter('contao.upload_path') . '/';
			$this->text = str_replace(' src="' . $path, ' src="' . $staticUrl . $path, (string) $this->text);
		}

		$this->Template->text = StringUtil::encodeEmail((string) $this->text);
		$this->Template->addImage = false;
		$this->Template->addBefore = false;

		// Add an image
		if ($this->addImage)
		{
			$figure = System::getContainer()
				->get('contao.image.studio')
				->createFigureBuilder()
				->from($this->singleSRC)
				->setSize($this->size)
				->setOverwriteMetadata($this->objModel->getOverwriteMetadata())
				->enableLightbox($this->fullsize)
				->buildIfResourceExists();

			$figure?->applyLegacyTemplateData($this->Template, null, $this->floating);
		}
	}
}
