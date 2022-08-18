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
 * Front end content element "module".
 */
class ContentModule extends ContentElement
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

		if (!$objModule = ModuleModel::findByPk($this->module))
		{
			return '';
		}

		// Clone the model, so we do not modify the shared model in the registry
		$objModel = $objModule->cloneOriginal();
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

		// Tag the content element (see #2137)
		if ($this->objModel !== null)
		{
			System::getContainer()->get('contao.cache.entity_tags')->tagWithModelInstance($this->objModel);
		}

		return Controller::getFrontendModule($objModel, $this->strColumn);
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
	}
}
