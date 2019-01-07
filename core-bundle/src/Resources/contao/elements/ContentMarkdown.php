<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Michelf\MarkdownExtra;

/**
 * Front end content element "code".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentMarkdown extends ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_markdown';

	/**
	 * Show the raw markdown code in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$return = '<pre>'. StringUtil::specialchars($this->code) .'</pre>';

			if ($this->headline != '')
			{
				$return = '<'. $this->hl .'>'. $this->headline .'</'. $this->hl .'>'. $return;
			}

			return $return;
		}

		return parent::generate();
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$this->code = MarkdownExtra::defaultTransform($this->code);
		$this->Template->content = strip_tags($this->code, Config::get('allowedTags'));
	}
}

class_alias(ContentMarkdown::class, 'ContentMarkdown');
