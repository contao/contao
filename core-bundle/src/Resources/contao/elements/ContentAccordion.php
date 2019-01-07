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
 * Front end content element "accordion".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContentAccordion extends ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_accordionSingle';

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		// Clean the RTE output
		$this->text = StringUtil::toHtml5($this->text);

		$this->Template->text = StringUtil::encodeEmail($this->text);
		$this->Template->addImage = false;

		// Add an image
		if ($this->addImage && $this->singleSRC != '')
		{
			$objModel = FilesModel::findByUuid($this->singleSRC);

			if ($objModel !== null && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objModel->path))
			{
				$this->singleSRC = $objModel->path;
				$this->addImageToTemplate($this->Template, $this->arrData, null, null, $objModel);
			}
		}

		$classes = StringUtil::deserialize($this->mooClasses);

		$this->Template->toggler = $classes[0] ?: 'toggler';
		$this->Template->accordion = $classes[1] ?: 'accordion';
		$this->Template->headlineStyle = $this->mooStyle;
		$this->Template->headline = $this->mooHeadline;
	}
}

class_alias(ContentAccordion::class, 'ContentAccordion');
