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
 * Class FormExplanation
 *
 * @property string $text
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FormExplanation extends Widget
{

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_explanation';

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-explanation';

	/**
	 * Do not validate
	 */
	public function validate()
	{
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		$this->text = StringUtil::toHtml5($this->text);

		// Add the static files URL to images
		if ($staticUrl = System::getContainer()->get('contao.assets.files_context')->getStaticUrl())
		{
			$path = Config::get('uploadPath') . '/';
			$this->text = str_replace(' src="' . $path, ' src="' . $staticUrl . $path, $this->text);
		}

		return StringUtil::encodeEmail($this->text);
	}
}

class_alias(FormExplanation::class, 'FormExplanation');
