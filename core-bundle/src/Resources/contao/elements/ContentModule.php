<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Front end content element "module".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentModule extends \ContentElement
{

	/**
	 * Parse the template
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'FE' && !BE_USER_LOGGED_IN && ($this->invisible || ($this->start != '' && $this->start > time()) || ($this->stop != '' && $this->stop < time())))
		{
			return '';
		}

		$objModule = \ModuleModel::findByPk($this->module);

		if ($objModule === null)
		{
			return '';
		}

		$strClass = \Module::findClass($objModule->type);

		if (!class_exists($strClass))
		{
			return '';
		}

		$objModule->typePrefix = 'ce_';

		/** @var Module $objModule */
		$objModule = new $strClass($objModule, $this->strColumn);

		$cssID = deserialize($objModule->cssID, true);

		// Override the CSS ID (see #305)
		if (!empty($this->cssID[0]))
		{
			$cssID[0] = $this->cssID[0];
		}

		// Merge the CSS classes (see #6011)
		if (!empty($this->cssID[1]))
		{
			$cssID[1] = trim($cssID[1] . ' ' . $this->cssID[1]);
		}

		$objModule->cssID = $cssID;

		return $objModule->generate();
	}


	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		return;
	}
}
