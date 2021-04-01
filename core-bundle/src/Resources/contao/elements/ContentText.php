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
 * Front end content element "text".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentText extends ContentElement
{
	use LegacyFigureBuilderTrait;

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
		$this->text = StringUtil::toHtml5($this->text);

		// Add the static files URL to images
		if ($staticUrl = System::getContainer()->get('contao.assets.files_context')->getStaticUrl())
		{
			$path = Config::get('uploadPath') . '/';
			$this->text = str_replace(' src="' . $path, ' src="' . $staticUrl . $path, $this->text);
		}

		$this->Template->text = StringUtil::encodeEmail($this->text);
		$this->Template->addImage = false;
		$this->Template->addBefore = false;

		// Add an image
		if ($this->addImage && null !== ($figureBuilder = $this->getFigureBuilderIfResourceExists($this->singleSRC)))
		{
			$figureBuilder
				->setSize($this->size)
				->setMetadata($this->objModel->getOverwriteMetadata())
				->enableLightbox((bool) $this->fullsize)
				->build()
				->applyLegacyTemplateData($this->Template, $this->imagemargin, $this->floating);
		}
	}
}

class_alias(ContentText::class, 'ContentText');
