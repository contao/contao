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
 * Front end content element "article alias".
 */
class ContentArticle extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_article';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$this->Template->article = $this->getArticle($this->articleAlias, false, true, $this->strColumn);
	}
}
