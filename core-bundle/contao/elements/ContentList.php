<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\ContentElement\ListController;

trigger_deprecation('contao/core-bundle', '5.6', 'Using the "%s" class is deprecated and will no longer work in Contao 6. Use the "%s" class instead.', ContentList::class, ListController::class);

/**
 * Front end content element "list".
 */
class ContentList extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_list';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$this->Template->items = StringUtil::deserialize($this->listitems, true);
		$this->Template->tag = ($this->listtype == 'ordered') ? 'ol' : 'ul';
	}
}
