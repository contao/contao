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
 * @property string  $type
 * @property boolean $mandatory
 * @property integer $min
 * @property integer $max
 * @property integer $step
 *
 * @author Fritz Michael Gschwantner <https://github.com/fritzmg>
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
			// Treat minlength/minval as min for number type field (#1622)
			case 'minlength':
			case 'minval':
				$this->min = $varValue;
				break;

			// Treat maxlength/maxval as max for number type field (#1622)
			case 'maxlength':
			case 'maxval':
				$this->max = $varValue;
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

			case 'min':
			case 'max':
				if ($varValue > 0)
				{
					$this->arrAttributes[$strKey] = $varValue;
					$this->arrConfiguration[$strKey.'val'] = $varValue;
				}
				else
				{
					unset($this->arrAttributes[$strKey], $this->arrConfiguration[$strKey.'val']);
				}
				unset($this->arrAttributes[$strKey.'length']);
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
	 * Return a parameter.
	 *
	 * @param string $strKey The parameter key
	 *
	 * @return mixed The parameter value
	 */
	public function __get($strKey)
	{
		switch ($strKey) {
			case 'type':
				return 'range';
				break;

			default:
				return parent::__get($strKey);
				break;
		}
	}

	/**
	 * Generate the widget and return it as string.
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		return sprintf('<input type="%s" name="%s" id="ctrl_%s" class="range%s%s" value="%s"%s%s',
						$this->type,
						$this->strName,
						$this->strId,
						($this->hideInput ? ' password' : ''),
						(('' !== $this->strClass) ? ' '.$this->strClass : ''),
						StringUtil::specialchars($this->value),
						$this->getAttributes(),
						$this->strTagEnding);
	}
}

class_alias(FormRange::class, 'FormRange');
