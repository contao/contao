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
 * Front end content element "accordion" (wrapper stop).
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

class_alias(ContentAccordionStop::class, 'ContentAccordionStop');
