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
 * @property string  $content
 * @property array   $sections
 * @property array   $positions
 * @property array   $matches
 *
 * @method static string getTemplate(string $strTemplate)
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
}
