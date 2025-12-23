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

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class is deprecated and will no longer work in Contao 6. Use the "%s" class instead.', ContentAccordionStart::class, AccordionController::class);
/**
 * Front end content element "accordion" (wrapper start).
 */
class ContentAccordionStart extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_accordionStart';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$this->strTemplate = 'be_wildcard';

			$this->Template = new BackendTemplate($this->strTemplate);
			$this->Template->title = $this->mooHeadline;
		}

		$classes = StringUtil::deserialize($this->mooClasses, true) + array(null, null);

		$this->Template->toggler = $classes[0] ?: 'toggler';
		$this->Template->accordion = $classes[1] ?: 'accordion';
		$this->Template->headlineStyle = StringUtil::specialcharsAttribute($this->mooStyle);
		$this->Template->headline = $this->mooHeadline;
	}
}
