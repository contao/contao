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
 * @deprecated Deprecated since Contao 6.0, to be removed in Contao 7
 */
trait TemplateInheritance
{
	/**
	 * Template file
	 * @var string
	 */
	protected $strTemplate;

	/**
	 * Parse the template file and return it as string
	 *
	 * @return string The template markup
	 */
	public function inherit()
	{
		trigger_deprecation('contao/core-bundle', '6.0', 'Using the TemplateInheritance trait to render templates is deprecated and will be removed in Contao 7, use \Twig\Environment#render() instead.');

		$contextFactory = System::getContainer()->get('contao.twig.interop.context_factory');

		$context = $this instanceof Template
			? $contextFactory->fromContaoTemplate($this)
			: $contextFactory->fromClass($this)
		;

		return System::getContainer()->get('twig')->render("@Contao/$this->strTemplate.html.twig", $context);
	}
}
