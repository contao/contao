<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;

/**
 * Class FormText
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
 */
class FormText extends Widget
{
	protected const HTML5_DATE_FORMAT = 'Y-m-d';

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
	protected $strTemplate = 'form_text';

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
			case 'minlength':
				if ($varValue > 0 && $this->rgxp != 'digit' && $this->rgxp != 'natural')
				{
					$this->arrAttributes['minlength'] =  $varValue;
				}
				break;

			case 'maxlength':
				if ($varValue > 0 && $this->rgxp != 'digit' && $this->rgxp != 'natural')
				{
					$this->arrAttributes['maxlength'] =  $varValue;
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
			case 'minval':
				if ($this->rgxp == 'digit' || $this->rgxp == 'natural')
				{
					$this->arrAttributes['min'] = $varValue;
				}
				break;

			case 'max':
			case 'maxval':
				if ($this->rgxp == 'digit' || $this->rgxp == 'natural')
				{
					$this->arrAttributes['max'] = $varValue;
				}
				break;

			case 'step':
				if ($varValue > 0 && $this->type == 'number')
				{
					$this->arrAttributes[$strKey] = $varValue;
				}
				else
				{
					unset($this->arrAttributes[$strKey]);
				}
				break;

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
				if ($this->rgxp == 'url' || $this->rgxp == HttpUrlListener::RGXP_NAME)
				{
					try
					{
						return Idna::decodeUrl($this->varValue);
					}
					catch (\InvalidArgumentException $e)
					{
						return $this->varValue;
					}
				}

				if ($this->rgxp == 'email' || $this->rgxp == 'friendly')
				{
					return Idna::decodeEmail($this->varValue);
				}

				return $this->varValue;

			case 'type':
				if ($this->hideInput)
				{
					return 'password';
				}

				// Use the HTML5 types (see #4138) but not the time and datetime types (see #5918)
				switch ($this->rgxp)
				{
					case 'digit':
						// Allow floats (see #7257)
						if (!isset($this->arrAttributes['step']))
						{
							$this->addAttribute('step', 'any');
						}
						// no break

					case 'natural':
						return 'number';

					case 'phone':
						return 'tel';

					case 'email':
						return 'email';

					case 'url':
					case HttpUrlListener::RGXP_NAME:
						return 'url';

					// We can use the HTML5 date type as the validation has been adjusted (see #4936)
					case 'date':
						return 'date';
				}

				return 'text';

			case 'min':
			case 'minval':
				if ($this->rgxp == 'digit')
				{
					return $this->arrAttributes['min'];
				}

				return parent::__get($strKey);

			case 'max':
			case 'maxval':
				if ($this->rgxp == 'digit')
				{
					return $this->arrAttributes['max'];
				}

				return parent::__get($strKey);

			default:
				return parent::__get($strKey);
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
		if ($this->rgxp == 'url' || $this->rgxp == HttpUrlListener::RGXP_NAME)
		{
			try
			{
				$varInput = Idna::encodeUrl($varInput);
			}
			catch (\InvalidArgumentException $e)
			{
			}
		}
		elseif ($this->rgxp == 'email' || $this->rgxp == 'friendly')
		{
			$varInput = Idna::encodeEmail($varInput);
		}
		elseif ($this->rgxp == 'date')
		{
			$targetFormat = Date::getNumericDateFormat();

			// Check if date format matches the HTML5 standard
			if (self::HTML5_DATE_FORMAT !== $targetFormat && preg_match('~^' . Date::getRegexp(self::HTML5_DATE_FORMAT) . '$~i', $varInput ?? ''))
			{
				// Transform to defined date format (see #5918)
				$date = \DateTimeImmutable::createFromFormat(self::HTML5_DATE_FORMAT, $varInput);
				$varInput = $date->format($targetFormat);
			}
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
		return sprintf(
			'<input type="%s" name="%s" id="ctrl_%s" class="text%s%s" value="%s"%s%s',
			$this->type,
			$this->strName,
			$this->strId,
			($this->hideInput ? ' password' : ''),
			($this->strClass ? ' ' . $this->strClass : ''),
			StringUtil::specialchars($this->value),
			$this->getAttributes(),
			$this->strTagEnding
		);
	}
}
