<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use FOS\HttpCache\ResponseTagger;

/**
 * Front end content element "module".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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

		$objModel = ModuleModel::findByPk($this->module);

		if ($objModel === null)
		{
			return '';
		}

		$strClass = Module::findClass($objModel->type);

		if (!class_exists($strClass))
		{
			return '';
		}

		$cssID = StringUtil::deserialize($objModel->cssID, true);

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

		// Clone the model so we do not modify the shared model in the registry
		$objModel = $objModel->cloneOriginal();
		$objModel->cssID = $cssID;
		$objModel->typePrefix = 'ce_';

		/** @var Module $objModule */
		$objModule = new $strClass($objModel, $this->strColumn);

		// Tag the response
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			/** @var ResponseTagger $responseTagger */
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags(array('contao.db.tl_content.' . $this->id));
		}

		return $objModule->generate();
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
	}
}

class_alias(ContentModule::class, 'ContentModule');
