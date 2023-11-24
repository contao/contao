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
 * Class FormSubmit
 *
 * @property string  $name
 * @property string  $label
 * @property string  $singleSRC
 * @property boolean $imageSubmit
 * @property boolean $required
 * @property boolean $mandatory
 * @property integer $minlength
 * @property integer $maxlength
 * @property string  $src
 */
class FormSubmit extends Widget
{
	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_submit';

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-submit';

	/**
	 * Add specific attributes
	 *
	 * @param string $strKey   The attribute name
	 * @param mixed  $varValue The attribute value
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'singleSRC':
				$this->arrConfiguration['singleSRC'] = $varValue;
				break;

			case 'imageSubmit':
				$this->arrConfiguration['imageSubmit'] = $varValue ? true : false;
				break;

			case 'name':
				$this->arrAttributes['name'] = $varValue;
				break;

			case 'label':
				$this->slabel = $varValue;
				break;

			case 'required':
			case 'mandatory':
			case 'minlength':
			case 'maxlength':
				// Ignore
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Do not validate
	 */
	public function validate()
	{
	}

	/**
	 * Parse the template file and return it as string
	 *
	 * @param array $arrAttributes An optional attributes array
	 *
	 * @return string The template markup
	 */
	public function parse($arrAttributes=null)
	{
		if ($this->imageSubmit && $this->singleSRC)
		{
			$objModel = FilesModel::findByUuid($this->singleSRC);

			if ($objModel !== null && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objModel->path))
			{
				$this->src = System::getContainer()->get('contao.assets.files_context')->getStaticUrl() . $objModel->path;
			}
		}

		return parent::parse($arrAttributes);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		if ($this->src)
		{
			return sprintf(
				'<input type="image" src="%s" id="ctrl_%s" class="submit%s" title="%s" alt="%s"%s%s',
				$this->src,
				$this->strId,
				$this->strClass ? ' ' . $this->strClass : '',
				StringUtil::specialchars($this->slabel),
				StringUtil::specialchars($this->slabel),
				$this->getAttributes(),
				$this->strTagEnding
			);
		}

		// Return the regular button
		return sprintf(
			'<button type="submit" id="ctrl_%s" class="submit%s"%s>%s</button>',
			$this->strId,
			$this->strClass ? ' ' . $this->strClass : '',
			$this->getAttributes(),
			$this->slabel
		);
	}
}
