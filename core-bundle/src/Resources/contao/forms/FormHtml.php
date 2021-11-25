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
 * Class FormHtml
 *
 * @property string $html
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FormHtml extends Widget
{
	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_html';

	/**
	 * Do not validate
	 */
	public function validate()
	{
	}

	/**
	 * Parse the template file and return it as string
	 *
	 * @param array $arrAttributes An optional attributes array
	 *
	 * @return string The template markup
	 *
	 * @deprecated Since Contao 4.13 will be made protected in Contao 5.0.
	 */
	public function parse($arrAttributes=null)
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$this->html = htmlspecialchars($this->html);
		}

		$strBuffer = parent::parse($arrAttributes);

		if (!is_a(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? null, parent::class, true))
		{
			trigger_deprecation('contao/core-bundle', '4.13', 'Calling "%s()" from outside has been deprecated and will be made protected in Contao 5.0. Use "%s::parseWithInsertTags()" instead.', __METHOD__, __CLASS__);

			return System::getContainer()->get('contao.insert_tag.parser')->replace($strBuffer);
		}

		return $strBuffer;
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			return htmlspecialchars($this->html);
		}

		return $this->html;
	}
}

class_alias(FormHtml::class, 'FormHtml');
