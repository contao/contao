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
 * Front end content element "alias".
 */
class ContentAlias extends ContentElement
{
	/**
	 * Parse the template
	 *
	 * @return string
	 */
	public function generate()
	{
		if ($this->isHidden())
		{
			return '';
		}

		if (!$objElement = ContentModel::findByPk($this->cteAlias))
		{
			return '';
		}

		// Tag the included element (see #5248)
		System::getContainer()->get('contao.cache.entity_tags')->tagWithModelInstance($objElement);

		// Clone the model, so we do not modify the shared model in the registry
		$objModel = $objElement->cloneOriginal();
		$objModel->origId = $objModel->origId ?: $objModel->id;
		$objModel->id = $this->id;

		$cssID = StringUtil::deserialize($objModel->cssID, true);

		// Override the CSS ID (see #305)
		if (!empty($this->cssID[0]))
		{
			$cssID[0] = $this->cssID[0];
		}

		// Merge the CSS classes (see #6011)
		if (!empty($this->cssID[1]))
		{
			$cssID[1] = trim(($cssID[1] ?? '') . ' ' . $this->cssID[1]);
		}

		$objModel->cssID = $cssID;

		return Controller::getContentElement($objModel, $this->strColumn);
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
	}
}
