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
