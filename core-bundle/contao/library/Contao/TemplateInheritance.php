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
	 * Parent template
	 * @var string
	 */
	protected $strParent;

	/**
	 * Default template
	 * @var string
	 */
	protected $strDefault;

	/**
	 * Output format
	 * @var string
	 */
	protected $strFormat = 'html5';

	/**
	 * Tag ending
	 * @var string
	 */
	protected $strTagEnding = '>';

	/**
	 * Blocks
	 * @var array
	 */
	protected $arrBlocks = array();

	/**
	 * Block names
	 * @var array
	 */
	protected $arrBlockNames = array();

	/**
	 * Buffer level
	 * @var int
	 */
	protected $intBufferLevel = 0;

	/**
	 * @var bool|null
	 */
	protected $blnDebug;

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

	public function setDebug(bool|null $debug = null): self
	{
		return $this;
	}

	/**
	 * Extend another template
	 *
	 * @param string $name The template name
	 */
	public function extend($name)
	{
	}

	/**
	 * Insert the content of the parent block
	 */
	public function parent()
	{
	}

	/**
	 * Start a new block
	 *
	 * @param string $name The block name
	 *
	 * @throws \Exception If a child templates contains nested blocks
	 */
	public function block($name)
	{
	}

	/**
	 * End a block
	 *
	 * @throws \Exception If there is no open block
	 */
	public function endblock()
	{
	}

	/**
	 * Insert a template
	 *
	 * @param string $name The template name
	 * @param array  $data An optional data array
	 */
	public function insert($name, array|null $data=null)
	{
	}

	/**
	 * Find a particular template file and return its path
	 *
	 * @param string  $strTemplate The name of the template
	 * @param string  $strFormat   The file extension
	 * @param boolean $blnDefault  If true, the default template path is returned
	 *
	 * @return string The path to the template file
	 */
	protected function getTemplatePath($strTemplate, $strFormat='html5', $blnDefault=false)
	{
		trigger_deprecation('contao/core-bundle', '6.0', 'Using the TemplateInheritance trait to resolve a template path is deprecated and will be removed in Contao 7, use the ContaoFilesystemLoader instead.');

		return System::getContainer()
			->get('contao.twig.filesystem_loader')
			->getSourceContext("@Contao/$strTemplate.html.twig")
			->getPath()
		;
	}
}
