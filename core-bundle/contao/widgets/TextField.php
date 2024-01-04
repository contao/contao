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
 * Provide methods to handle text fields.
 *
 * @property integer $maxlength
 * @property boolean $mandatory
 * @property string  $placeholder
 * @property boolean $multiple
 * @property boolean $hideInput
 * @property integer $size
 */
class TextField extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Add a for attribute
	 * @var boolean
	 */
	protected $blnForAttribute = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * Disable the for attribute if the "multiple" option is set
	 *
	 * @param array $arrAttributes
	 */
	public function __construct($arrAttributes=null)
	{
		parent::__construct($arrAttributes);

		if ($this->multiple)
		{
			$this->blnForAttribute = false;
		}
	}

	/**
	 * Add specific attributes
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'maxlength':
				if ($varValue > 0)
				{
					$this->arrAttributes['maxlength'] = $varValue;
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

			case 'placeholder':
				$this->arrAttributes['placeholder'] = $varValue;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Trim values
	 *
	 * @param mixed $varInput
	 *
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		if (\is_array($varInput))
		{
			return parent::validator($varInput);
		}

		if (!$this->multiple)
		{
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
		}

		return parent::validator($varInput);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$strType = $this->hideInput ? 'password' : 'text';

		if (!$this->multiple)
		{
			// Hide the Punycode format (see #2750)
			if ($this->rgxp == 'url' || $this->rgxp == HttpUrlListener::RGXP_NAME)
			{
				try
				{
					$this->varValue = Idna::decodeUrl($this->varValue);
				}
				catch (\InvalidArgumentException $e)
				{
				}
			}
			elseif ($this->rgxp == 'email' || $this->rgxp == 'friendly')
			{
				$this->varValue = Idna::decodeEmail($this->varValue);
			}

			return sprintf(
				'<input type="%s" name="%s" id="ctrl_%s" class="tl_text%s" value="%s"%s data-action="focus->contao--scroll-offset#store">%s',
				$strType,
				$this->strName,
				$this->strId,
				$this->strClass ? ' ' . $this->strClass : '',
				self::specialcharsValue($this->varValue),
				$this->getAttributes(),
				$this->wizard
			);
		}

		// Return if field size is missing
		if (!$this->size)
		{
			return '';
		}

		if (!\is_array($this->varValue))
		{
			$this->varValue = array($this->varValue);
		}

		$arrFields = array();
		$blnPlaceholderArray = isset($this->arrAttributes['placeholder']) && \is_array($this->arrAttributes['placeholder']);

		for ($i=0; $i<$this->size; $i++)
		{
			$arrFields[] = sprintf(
				'<input type="%s" name="%s[]" id="ctrl_%s" class="tl_text_%s" value="%s"%s%s data-action="focus->contao--scroll-offset#store">',
				$strType,
				$this->strName,
				$this->strId . '_' . $i,
				$this->size,
				self::specialcharsValue(@$this->varValue[$i]), // see #4979
				$blnPlaceholderArray && isset($this->arrAttributes['placeholder'][$i]) ? ' placeholder="' . $this->arrAttributes['placeholder'][$i] . '"' : '',
				$this->getAttributes($blnPlaceholderArray ? array('placeholder') : array())
			);
		}

		return sprintf(
			'<div id="ctrl_%s" class="tl_text_field%s">%s</div>%s',
			$this->strId,
			$this->strClass ? ' ' . $this->strClass : '',
			implode(' ', $arrFields),
			$this->wizard
		);
	}
}
