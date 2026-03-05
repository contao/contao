<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\ContentElement\ToplinkController;

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class is deprecated and will no longer work in Contao 6. Use the "%s" class instead.', ContentToplink::class, ToplinkController::class);

/**
 * Front end content element "toplink".
 *
 * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
 *             use Contao\CoreBundle\Controller\ContentElement\ToplinkController instead.
 */
class ContentToplink extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_toplink';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		if (!$this->linkTitle)
		{
			$this->linkTitle = $GLOBALS['TL_LANG']['MSC']['backToTop'];
		}

		$this->Template->label = $this->linkTitle;
		$this->Template->title = StringUtil::specialchars($this->linkTitle);
		$this->Template->request = StringUtil::ampersand(Environment::get('requestUri'));
	}
}
