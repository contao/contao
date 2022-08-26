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
 * Front end module "HTML".
 */
class ModuleHtml extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_html';

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$this->Template->html = '<pre>' . htmlspecialchars($this->html) . '</pre>';
		}
		else
		{
			$this->Template->html = $this->html;
		}
	}
}

class_alias(ModuleHtml::class, 'ModuleHtml');
