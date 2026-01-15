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

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class is deprecated and will no longer work in Contao 6. Use the "%s" class instead.', ContentAccordionStop::class, AccordionController::class);

/**
 * Front end content element "accordion" (wrapper stop).
 *
 * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
 *             use Contao\CoreBundle\Controller\ContentElement\AccordionController instead.
 */
class ContentAccordionStop extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_accordionStop';

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
		}
	}
}
