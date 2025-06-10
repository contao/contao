<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\ContentElement\HtmlController;

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class has been deprecated and will no longer work in Contao 6. Use the "%s" class instead.', ContentHtml::class, HtmlController::class);

/**
 * Front end content element "HTML".
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
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$this->Template->html = '<pre>' . htmlspecialchars($this->html, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5) . '</pre>';
		}
		else
		{
			$this->Template->html = $this->html;
		}
	}
}
