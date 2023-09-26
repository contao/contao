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

trigger_deprecation('contao/core-bundle', '4.12', 'ContentMarkdown has been deprecated and will be removed in Contao 5.0.');

/**
 * Front end content element "code".
 */
class ContentMarkdown extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_markdown';

	/**
	 * Show the raw Markdown code in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$return = '<pre>' . StringUtil::specialchars($this->code) . '</pre>';

			if ($this->headline)
			{
				$return = '<' . $this->hl . '>' . $this->headline . '</' . $this->hl . '>' . $return;
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
		if (!class_exists(MarkdownExtra::class))
		{
			throw new \RuntimeException('You are using the deprecated Markdown content element class. If you want to keep using it, make sure to require "michelf/php-markdown" in your composer.json manually.');
		}

		$this->code = MarkdownExtra::defaultTransform($this->code);
		$this->Template->content = Input::stripTags($this->code, Config::get('allowedTags'), Config::get('allowedAttributes'));
	}
}

class_alias(ContentMarkdown::class, 'ContentMarkdown');
