<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

trigger_deprecation('contao/core-bundle', '4.13', 'Using the "Contao\TextStore" widget has been deprecated and will no longer work in Contao 5.0. Use the password widget instead.');

/**
 * A TextStore field is used to enter data only. It will not show the
 * currently stored value (useful e.g. to store passwords).
 *
 * @property integer $maxlength
 *
 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0.
 *             Use the password widget instead.
 */
class TextStore extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * Add specific attributes
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		if ($strKey == 'maxlength')
		{
			if ($varValue > 0)
			{
				$this->arrAttributes['maxlength'] = $varValue;
			}
		}
		else
		{
			parent::__set($strKey, $varValue);
		}
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		return sprintf(
			'<input type="password" name="%s" id="ctrl_%s" class="tl_text%s" value=""%s onfocus="Backend.getScrollOffset()">%s',
			$this->strName,
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			$this->getAttributes(),
			$this->wizard
		);
	}
}

class_alias(TextStore::class, 'TextStore');
