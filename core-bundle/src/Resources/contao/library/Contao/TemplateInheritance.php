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
 * Provides the template inheritance logic
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
	 * Parse the template file and return it as string
	 *
	 * @return string The template markup
	 */
	public function inherit()
	{
		$strBuffer = '';

		// Start with the template itself
		$this->strParent = $this->strTemplate;

		// Include the parent templates
		while ($this->strParent !== null)
		{
			$strCurrent = $this->strParent;
			$strParent = $this->strDefault ?: $this->getTemplatePath($this->strParent, $this->strFormat);

			// Reset the flags
			$this->strParent = null;
			$this->strDefault = null;

			ob_start();
			$this->intBufferLevel = 1;

			try
			{
				include $strParent;

				// Capture the output of the root template
				if ($this->strParent === null)
				{
					$strBuffer = ob_get_contents();
				}
				elseif ($this->strParent == $strCurrent)
				{
					$this->strDefault = $this->getTemplatePath($this->strParent, $this->strFormat, true);
				}
			}
			finally
			{
				for ($i=0; $i<$this->intBufferLevel; $i++)
				{
					ob_end_clean();
				}
			}
		}

		// Reset the internal arrays
		$this->arrBlocks = array();

		// Add start and end markers in debug mode
		if (Config::get('debugMode'))
		{
			$strRelPath = StringUtil::stripRootDir($this->getTemplatePath($this->strTemplate, $this->strFormat));
			$strBuffer = "\n<!-- TEMPLATE START: $strRelPath -->\n$strBuffer\n<!-- TEMPLATE END: $strRelPath -->\n";
		}

		return $strBuffer;
	}

	/**
	 * Extend another template
	 *
	 * @param string $name The template name
	 */
	public function extend($name)
	{
		$this->strParent = $name;
	}

	/**
	 * Insert the content of the parent block
	 */
	public function parent()
	{
		echo '[[TL_PARENT]]';
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
		$this->arrBlockNames[] = $name;

		// Root template
		if ($this->strParent === null)
		{
			// Register the block name
			if (!isset($this->arrBlocks[$name]))
			{
				$this->arrBlocks[$name] = '[[TL_PARENT]]';
			}

			// Combine the contents of the child blocks
			elseif (\is_array($this->arrBlocks[$name]))
			{
				$callback = function ($current, $parent) {
					return str_replace('[[TL_PARENT]]', $parent, $current);
				};

				$this->arrBlocks[$name] = array_reduce($this->arrBlocks[$name], $callback, '[[TL_PARENT]]');
			}

			// Handle nested blocks
			if ($this->arrBlocks[$name] != '[[TL_PARENT]]')
			{
				// Output everything before the first TL_PARENT tag
				if (strpos($this->arrBlocks[$name], '[[TL_PARENT]]') !== false)
				{
					list($content) = explode('[[TL_PARENT]]', $this->arrBlocks[$name], 2);
					echo $content;
				}

				// Output the current block and start a new output buffer to remove the following blocks
				else
				{
					echo $this->arrBlocks[$name];
					ob_start();
					++$this->intBufferLevel;
				}
			}
		}

		// Child template
		else
		{
			// Clean the output buffer
			ob_clean();

			// Check for nested blocks
			if (\count($this->arrBlockNames) > 1)
			{
				throw new \Exception('Nested blocks are not allowed in child templates');
			}
		}
	}

	/**
	 * End a block
	 *
	 * @throws \Exception If there is no open block
	 */
	public function endblock()
	{
		// Check for open blocks
		if (empty($this->arrBlockNames))
		{
			throw new \Exception('You must start a block before you can end it');
		}

		// Get the block name
		$name = array_pop($this->arrBlockNames);

		// Root template
		if ($this->strParent === null)
		{
			// Handle nested blocks
			if ($this->arrBlocks[$name] != '[[TL_PARENT]]')
			{
				// Output everything after the first TL_PARENT tag
				if (strpos($this->arrBlocks[$name], '[[TL_PARENT]]') !== false)
				{
					list(, $content) = explode('[[TL_PARENT]]', $this->arrBlocks[$name], 2);
					echo $content;
				}

				// Remove the overwritten content
				else
				{
					ob_end_clean();
					--$this->intBufferLevel;
				}
			}
		}

		// Child template
		else
		{
			// Capture the block content
			$this->arrBlocks[$name][] = ob_get_clean();

			// Start a new output buffer
			ob_start();
		}
	}

	/**
	 * Insert a template
	 *
	 * @param string $name The template name
	 * @param array  $data An optional data array
	 */
	public function insert($name, array $data=null)
	{
		/** @var Template $tpl */
		if ($this instanceof Template)
		{
			$tpl = new static($name);
		}
		elseif (TL_MODE == 'BE')
		{
			$tpl = new BackendTemplate($name);
		}
		else
		{
			$tpl = new FrontendTemplate($name);
		}

		if ($data !== null)
		{
			$tpl->setData($data);
		}

		echo $tpl->parse();
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
		if ($blnDefault)
		{
			return TemplateLoader::getDefaultPath($strTemplate, $strFormat);
		}

		return Controller::getTemplate($strTemplate);
	}
}

class_alias(TemplateInheritance::class, 'TemplateInheritance');
