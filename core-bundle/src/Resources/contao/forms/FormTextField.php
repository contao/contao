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
 * Class FormTextField
 *
 * @property string  $value
 * @property string  $type
 * @property integer $maxlength
 * @property boolean $mandatory
 * @property integer $min
 * @property integer $max
 * @property integer $step
 * @property string  $placeholder
 * @property boolean $hideInput
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FormTextField extends Widget
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
	protected $strTemplate = 'form_textfield';

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-text';

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
				if ($this->type === 'number')
				{
					$this->min = $varValue;
				}
				else
				{
					$this->arrConfiguration[$strKey] = $varValue;
				}
				break;

			// Treat maxlength/maxval as max for number type field (#1622)
			case 'maxlength':
			case 'maxval':
				if ($varValue > 0)
				{
					if ($this->type === 'number')
					{
						$this->max = $varValue;
					}
					elseif ($strKey === 'maxlength')
					{
						$this->arrAttributes[$strKey] = $varValue;
					}
					else
					{
						$this->arrConfiguration[$strKey] = $varValue;
					}
				}
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
				$this->arrAttributes[$strKey] = $varValue;
				$this->arrConfiguration[$strKey . 'val'] = $varValue;
				unset($this->arrAttributes[$strKey . 'length']);
				break;

			case 'step':
			case 'placeholder':
				$this->arrAttributes[$strKey] = $varValue;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Return a parameter
	 *
	 * @param string $strKey The parameter key
	 *
	 * @return mixed The parameter value
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'value':
				// Hide the Punycode format (see #2750)
				if ($this->rgxp == 'url')
				{
					try
					{
						return \Idna::decodeUrl($this->varValue);
					}
					catch (\InvalidArgumentException $e)
					{
						return $this->varValue;
					}
				}
				elseif ($this->rgxp == 'email' || $this->rgxp == 'friendly')
				{
					return \Idna::decodeEmail($this->varValue);
				}
				else
				{
					return $this->varValue;
				}
				break;

			case 'type':
				if ($this->hideInput)
				{
					return 'password';
				}

				// Use the HTML5 types (see #4138) but not the date, time and datetime types (see #5918)
				switch ($this->rgxp)
				{
					case 'digit':
						// Allow floats (see #7257)
						if (!isset($this->arrAttributes['step']))
						{
							$this->addAttribute('step', 'any');
						}
						// NO break; here

					case 'natural':
						return 'number';
						break;

					case 'phone':
						return 'tel';
						break;

					case 'email':
						return 'email';
						break;

					case 'url':
						return 'url';
						break;
				}

				return 'text';
				break;

			default:
				return parent::__get($strKey);
				break;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function addAttributes($arrAttributes)
	{
		parent::addAttributes($arrAttributes);

		// Re-add some attributes if the field type is a number
		if ($this->type === 'number')
		{
			foreach (['minlength', 'minval', 'maxlength', 'maxval'] as $name)
			{
				if (isset($arrAttributes[$name]))
				{
					$this->$name = $arrAttributes[$name];
				}
			}
		}
	}

	/**
	 * Trim the values
	 *
	 * @param mixed $varInput The user input
	 *
	 * @return mixed The validated user input
	 */
	protected function validator($varInput)
	{
		if (\is_array($varInput))
		{
			return parent::validator($varInput);
		}

		// Convert to Punycode format (see #5571)
		if ($this->rgxp == 'url')
		{
			try
			{
				$varInput = \Idna::encodeUrl($varInput);
			}
			catch (\InvalidArgumentException $e) {}
		}
		elseif ($this->rgxp == 'email' || $this->rgxp == 'friendly')
		{
			$varInput = \Idna::encodeEmail($varInput);
		}

		return parent::validator($varInput);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string The widget markup
	 */
	public function generate()
	{
		return sprintf('<input type="%s" name="%s" id="ctrl_%s" class="text%s%s" value="%s"%s%s',
						$this->type,
						$this->strName,
						$this->strId,
						($this->hideInput ? ' password' : ''),
						(($this->strClass != '') ? ' ' . $this->strClass : ''),
						\StringUtil::specialchars($this->value),
						$this->getAttributes(),
						$this->strTagEnding);
	}
}

class_alias(FormTextField::class, 'FormTextField');
