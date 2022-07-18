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
 * Front end module "form".
 */
class ModuleForm extends Module
{
	/**
	 * Parse the template
	 *
	 * @return string
	 */
	public function generate()
	{
		$objForm = FormModel::findByPk($this->form);

		if ($objForm === null)
		{
			return '';
		}

		// Clone the model, so we do not modify the shared model in the registry
		$objModel = $objForm->cloneOriginal();
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

		return Controller::getForm($objModel, $this->strColumn, true);
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
	}
}
