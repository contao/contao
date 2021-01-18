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
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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

		$this->Template->headline = $this->mooHeadline;
		$this->Template->addWrapper = true;

		$prev = ContentModel::findOneBy(array(
			'ptable = ?',
			'pid = ?',
			'sorting < ?',
		), array(
			$this->ptable,
			$this->pid,
			$this->sorting,
		), array(
			'order' => 'sorting DESC',
		));

		if (null !== $prev && 'accordionStop' !== $prev->type)
		{
			$this->Template->addWrapper = false;
		}
	}
}

class_alias(ContentAccordionStart::class, 'ContentAccordionStart');
