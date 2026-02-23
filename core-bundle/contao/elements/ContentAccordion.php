<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\ContentElement\AccordionController;

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class is deprecated and will no longer work in Contao 6. Use the "%s" class instead.', ContentAccordion::class, AccordionController::class);

/**
 * Front end content element "accordion".
 *
 * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
 *             use Contao\CoreBundle\Controller\ContentElement\AccordionController instead.
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

		$classes = StringUtil::deserialize($this->mooClasses, true) + array(null, null);

		$this->Template->toggler = $classes[0] ?: 'toggler';
		$this->Template->accordion = $classes[1] ?: 'accordion';
		$this->Template->headlineStyle = StringUtil::specialcharsAttribute($this->mooStyle);
		$this->Template->headline = $this->mooHeadline;
	}
}
