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
 * Front end content element "alias".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentAlias extends \ContentElement
{

	/**
	 * Parse the template
	 *
	 * @return string
	 */
	public function generate()
	{
		$objElement = \ContentModel::findByPk($this->cteAlias);

		if ($objElement === null)
		{
			return '';
		}

		$strClass = static::findClass($objElement->type);

		if (!class_exists($strClass))
		{
			return '';
		}

		$objElement->origId = $objElement->id;
		$objElement->id = $this->id;
		$objElement->typePrefix = 'ce_';

		/** @var \ContentElement $objElement */
		$objElement = new $strClass($objElement);

		$cssID = deserialize($objElement->cssID, true);

		// Merge the CSS classes (see #6011)
		if (!empty($this->cssID[1]))
		{
			$cssID[1] = trim($cssID[1] . ' ' . $this->cssID[1]);
		}

		$objElement->cssID = $cssID;

		return $objElement->generate();
	}


	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		return;
	}
}
