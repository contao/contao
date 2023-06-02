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
 * Class FormRange
 *
 * @property string  $value
 * @property boolean $mandatory
 * @property integer $min
 * @property integer $max
 * @property integer $step
 */
class FormRange extends Widget
{
	/**
	 * Submit user input
	 *
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Add a for attribute
	 *
	 * @var boolean
	 */
	protected $blnForAttribute = true;

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_range';

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-range';

	/**
	 * Add specific attributes
	 *
	 * @param string $strKey   The attribute key
	 * @param mixed  $varValue The attribute value
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'min':
			case 'minval':
				$this->arrAttributes['min'] = $varValue;
				break;

			case 'max':
			case 'maxval':
				$this->arrAttributes['max'] = $varValue;
				break;

			case 'mandatory':
				if ($varValue)
				{
					$this->arrAttributes['required'] = 'required';
				}
				else
				{
					unset($this->arrAttributes['required']);
				}
				parent::__set($strKey, $varValue);
				break;

			case 'step':
				if ($varValue > 0)
				{
					$this->arrAttributes[$strKey] = $varValue;
				}
				else
				{
					unset($this->arrAttributes[$strKey]);
				}
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		return sprintf(
			'<input type="%s" name="%s" id="ctrl_%s" class="range%s" value="%s"%s%s',
			$this->type,
			$this->strName,
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			StringUtil::specialchars($this->value),
			$this->getAttributes(),
			$this->strTagEnding
		);
	}
}
