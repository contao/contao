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
 * Class FormFieldsetSTop
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FormFieldsetStop extends Widget
{

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_fieldsetStop';

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
		// Return a wildcard in the back end
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');

			return $objTemplate->parse();
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
		return '</fieldset>';
	}
}

class_alias(FormFieldsetStop::class, 'FormFieldsetStop');
