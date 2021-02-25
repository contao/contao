<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Image\Studio\Studio;

/**
 * Front end content element "accordion".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentAccordion extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_accordionSingle';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		// Clean the RTE output
		$this->text = StringUtil::toHtml5($this->text);

		$this->Template->text = StringUtil::encodeEmail($this->text);
		$this->Template->addImage = false;
		$this->Template->addBefore = false;

		// Add an image
		if ($this->addImage)
		{
			$figure = System::getContainer()->get(Studio::class)->createFigureBuilder()
				->from($this->singleSRC)
				->setSize($this->size)
				->setMetadata($this->objModel->getOverwriteMetadata())
				->enableLightbox((bool) $this->fullsize)
				->buildIfResourceExists();

			if (null !== $figure)
			{
				$figure->applyLegacyTemplateData($this->Template, $this->imagemargin, $this->floating);
			}
		}

		$classes = StringUtil::deserialize($this->mooClasses);

		$this->Template->toggler = $classes[0] ?: 'toggler';
		$this->Template->accordion = $classes[1] ?: 'accordion';
		$this->Template->headlineStyle = $this->mooStyle;
		$this->Template->headline = $this->mooHeadline;
	}
}

class_alias(ContentAccordion::class, 'ContentAccordion');
