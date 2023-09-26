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
 * Provide methods to handle text fields with unit drop down menu.
 *
 * @property integer $maxlength
 * @property array   $options
 */
class TrblField extends Widget
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
		switch ($strKey)
		{
			case 'maxlength':
				if ($varValue > 0)
				{
					$this->arrAttributes['maxlength'] = $varValue;
				}
				break;

			case 'options':
				$this->arrOptions = StringUtil::deserialize($varValue);
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * @param mixed $varInput
	 *
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		foreach ($varInput as $k=>$v)
		{
			if ($k == 'unit')
			{
				if (!$this->isValidOption($v))
				{
					$varInput[$k] = '';
					$this->addError($GLOBALS['TL_LANG']['ERR']['invalid']);
				}
			}
			else
			{
				$varInput[$k] = parent::validator($v);
			}
		}

		return $varInput;
	}

	/**
	 * Only check against the unit values (see #7246)
	 *
	 * @param array $arrOption The options array
	 *
	 * @return string The "selected" attribute or an empty string
	 */
	protected function isSelected($arrOption)
	{
		if (empty($this->varValue) && empty($_POST) && ($arrOption['default'] ?? null))
		{
			return $this->optionSelected(1, 1);
		}

		if (empty($this->varValue) || !\is_array($this->varValue))
		{
			return '';
		}

		return $this->optionSelected($arrOption['value'] ?? null, $this->varValue['unit'] ?? null);
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$arrUnits = array();

		foreach ($this->arrOptions as $arrUnit)
		{
			$arrUnits[] = sprintf(
				'<option value="%s"%s>%s</option>',
				StringUtil::specialchars($arrUnit['value']),
				$this->isSelected($arrUnit),
				$arrUnit['label']
			);
		}

		$arrFields = array();
		$arrKeys = array('top', 'right', 'bottom', 'left');

		if (!\is_array($this->varValue))
		{
			$this->varValue = array();
		}

		foreach ($arrKeys as $strKey)
		{
			$arrFields[] = sprintf(
				'<input type="text" name="%s[%s]" id="ctrl_%s" class="tl_text_trbl trbl_%s%s" value="%s"%s onfocus="Backend.getScrollOffset()">',
				$this->strName,
				$strKey,
				$this->strId . '_' . $strKey,
				$strKey,
				($this->strClass ? ' ' . $this->strClass : ''),
				StringUtil::specialchars(@$this->varValue[$strKey]), // see #4979
				$this->getAttributes()
			);
		}

		return sprintf(
			'%s <select name="%s[unit]" class="tl_select_unit" onfocus="Backend.getScrollOffset()"%s>%s</select>%s',
			implode(' ', $arrFields),
			$this->strName,
			$this->getAttribute('disabled'),
			implode('', $arrUnits),
			$this->wizard
		);
	}
}

class_alias(TrblField::class, 'TrblField');
