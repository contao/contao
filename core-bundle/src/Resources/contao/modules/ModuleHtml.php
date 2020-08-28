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
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
		$request = Controller::getCurrentRequest();

		if (System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request))
		{
			return $this->html;
		}

		return htmlspecialchars($this->html);
	}
}

class_alias(ModuleHtml::class, 'ModuleHtml');
