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
 * @property integer $id
 * @property string  $keywords
 * @property string  $content
 * @property array   $sections
 * @property array   $positions
 * @property array   $matches
 * @method   string  getTemplate(string $strTemplate)
 *
 * @internal
 */
trait FrontendTemplateTrait
{
	/**
	 * Return a custom layout section
	 *
	 * @param string $key      The section name
	 * @param string $template An optional template name
	 */
	public function section($key, $template=null)
	{
		if (empty($this->sections[$key]))
		{
			return;
		}

		$this->id = $key;
		$this->content = $this->sections[$key];

		if ($template === null)
		{
			foreach ($this->positions as $position)
			{
				if (isset($position[$key]['template']))
				{
					$template = $position[$key]['template'];
				}
			}
		}

		if ($template === null)
		{
			$template = 'block_section';
		}

		include $this->getTemplate($template);
	}

	/**
	 * Return the custom layout sections
	 *
	 * @param string $key      An optional section name
	 * @param string $template An optional template name
	 */
	public function sections($key=null, $template=null)
	{
		if (!array_filter($this->sections))
		{
			return;
		}

		// The key does not match
		if ($key && !isset($this->positions[$key]))
		{
			return;
		}

		$matches = array();

		foreach ($this->positions[$key] as $id=>$section)
		{
			if (!empty($this->sections[$id]))
			{
				if (!isset($section['template']))
				{
					$section['template'] = 'block_section';
				}

				$section['content'] = $this->sections[$id];
				$matches[$id] = $section;
			}
		}

		// Return if the section is empty (see #1115)
		if (empty($matches))
		{
			return;
		}

		$this->matches = $matches;

		if ($template === null)
		{
			$template = 'block_sections';
		}

		include $this->getTemplate($template);
	}

	/**
	 * Point to `Frontend::addToUrl()` in front end templates (see #6736)
	 *
	 * @param string  $strRequest      The request string to be added
	 * @param boolean $blnIgnoreParams If true, the $_GET parameters will be ignored
	 * @param array   $arrUnset        An optional array of keys to unset
	 *
	 * @return string The new URI string
	 */
	public static function addToUrl($strRequest, $blnIgnoreParams=false, $arrUnset=array())
	{
		return Frontend::addToUrl($strRequest, $blnIgnoreParams, $arrUnset);
	}

	/**
	 * Check whether there is an authenticated back end user
	 *
	 * @return boolean True if there is an authenticated back end user
	 */
	public function hasAuthenticatedBackendUser()
	{
		return System::getContainer()->get('contao.security.token_checker')->hasBackendUser();
	}

	/**
	 * Add the template output to the cache and add the cache headers
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use proper response caching headers instead.
	 */
	protected function addToCache()
	{
		trigger_deprecation('contao/core-bundle', '4.3', 'Using "Contao\FrontendTemplate::addToCache()" has been deprecated and will no longer work in Contao 5.0. Use proper response caching headers instead.');
	}

	/**
	 * Add the template output to the search index
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use the kernel.terminate event instead.
	 */
	protected function addToSearchIndex()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\FrontendTemplate::addToSearchIndex()" has been deprecated and will no longer work in Contao 5.0. Use the "kernel.terminate" event instead.');
	}

	/**
	 * Return a custom layout section
	 *
	 * @param string $strKey The section name
	 *
	 * @return string The section markup
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use FrontendTemplate::section() instead.
	 */
	public function getCustomSection($strKey)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\FrontendTemplate::getCustomSection()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\FrontendTemplate::section()" instead.');

		return '<div id="' . $strKey . '">' . $this->sections[$strKey] . '</div>' . "\n";
	}

	/**
	 * Return all custom layout sections
	 *
	 * @param string $strKey An optional section name
	 *
	 * @return string The section markup
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use FrontendTemplate::sections() instead.
	 */
	public function getCustomSections($strKey=null)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\FrontendTemplate::getCustomSections()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\FrontendTemplate::sections()" instead.');

		if ($strKey && !isset($this->positions[$strKey]))
		{
			return '';
		}

		$tag = 'div';

		// Use the section tag for the main column
		if ($strKey == 'main')
		{
			$tag = 'section';
		}

		$sections = '';

		// Standardize the IDs (thanks to Tsarma) (see #4251)
		foreach ($this->positions[$strKey] as $sect)
		{
			if (isset($this->sections[$sect['id']]))
			{
				$sections .= "\n" . '<' . $tag . ' id="' . StringUtil::standardize($sect['id'], true) . '">' . "\n" . '<div class="inside">' . "\n" . $this->sections[$sect['id']] . "\n" . '</div>' . "\n" . '</' . $tag . '>' . "\n";
			}
		}

		if (!$sections)
		{
			return '';
		}

		return '<div class="custom">' . "\n" . $sections . "\n" . '</div>' . "\n";
	}
}
