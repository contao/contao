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
 * Front end content element "HTML".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentHtml extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_html';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$request = Controller::getCurrentRequest();

		if (System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request))
		{
			$this->Template->html = $this->html;
		}
		else
		{
			$this->Template->html = '<pre>' . htmlspecialchars($this->html) . '</pre>';
		}
	}
}

class_alias(ContentHtml::class, 'ContentHtml');
