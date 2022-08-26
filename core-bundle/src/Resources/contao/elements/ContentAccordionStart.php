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
		$this->Template->headlineStyle = $this->mooStyle;
		$this->Template->headline = $this->mooHeadline;
	}
}

class_alias(ContentAccordionStart::class, 'ContentAccordionStart');
