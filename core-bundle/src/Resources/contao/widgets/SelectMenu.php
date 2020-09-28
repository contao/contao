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
 * Provide methods to handle select menus.
 *
 * @property boolean $mandatory
 * @property integer $size
 * @property boolean $multiple
 * @property array   $options
 * @property array   $unknownOption
 * @property boolean $chosen
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class SelectMenu extends \Widget
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

			case 'size':
				if ($this->multiple)
				{
					$this->arrAttributes['size'] = $varValue;
				}
				break;

			case 'multiple':
				if ($varValue)
				{
					$this->arrAttributes['multiple'] = 'multiple';
				}
				break;

			case 'options':
				$this->arrOptions = \StringUtil::deserialize($varValue);
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Check for a valid option (see #4383)
	 */
	public function validate()
	{
		$varValue = $this->getPost($this->strName);

		if (!empty($varValue) && !$this->isValidOption($varValue))
		{
			$this->addError($GLOBALS['TL_LANG']['ERR']['invalid']);
		}

		parent::validate();
	}

	/**
	 * Check whether an input is one of the given options
	 *
	 * @param mixed $varInput The input string or array
	 *
	 * @return boolean True if the selected option exists
	 */
	protected function isValidOption($varInput)
	{
		if (parent::isValidOption($varInput))
		{
			return true;
		}

		if ($varInput == $this->unknownOption[0])
		{
			return true;
		}

		return false;
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$arrOptions = array();
		$strClass = 'tl_select';

		if ($this->multiple)
		{
			$this->strName .= '[]';
			$strClass = 'tl_mselect';
		}

		// Add an empty option if there are none
		if (empty($this->arrOptions) || !\is_array($this->arrOptions))
		{
			$this->arrOptions = array(array('value'=>'', 'label'=>'-'));
		}

		foreach ($this->arrOptions as $strKey=>$arrOption)
		{
			if (isset($arrOption['value']))
			{
				$arrOptions[] = sprintf(
					'<option value="%s"%s>%s</option>',
					\StringUtil::specialchars($arrOption['value']),
					$this->isSelected($arrOption),
					$arrOption['label']
				);
			}
			else
			{
				$arrOptgroups = array();

				foreach ($arrOption as $arrOptgroup)
				{
					$arrOptgroups[] = sprintf(
						'<option value="%s"%s>%s</option>',
						\StringUtil::specialchars($arrOptgroup['value']),
						$this->isSelected($arrOptgroup),
						$arrOptgroup['label']
					);
				}

				$arrOptions[] = sprintf('<optgroup label="&nbsp;%s">%s</optgroup>', \StringUtil::specialchars($strKey), implode('', $arrOptgroups));
			}
		}

		// If the user cannot select the current value, add it as unknown option,
		// so it does not get lost when saving the record (see #920)
		if (isset($this->unknownOption))
		{
			$arrOptions[] = sprintf(
				'<option value="%s" selected>%s</option>',
				StringUtil::specialchars($this->unknownOption[0]),
				$GLOBALS['TL_LANG']['MSC']['unknownOption']
			);
		}

		// Chosen
		if ($this->chosen)
		{
			$strClass .= ' tl_chosen';
		}

		return sprintf(
			'%s<select name="%s" id="ctrl_%s" class="%s%s"%s onfocus="Backend.getScrollOffset()">%s</select>%s',
			($this->multiple ? '<input type="hidden" name="' . (substr($this->strName, -2) == '[]' ? substr($this->strName, 0, -2) : $this->strName) . '" value="">' : ''),
			$this->strName,
			$this->strId,
			$strClass,
			(($this->strClass != '') ? ' ' . $this->strClass : ''),
			$this->getAttributes(),
			implode('', $arrOptions),
			$this->wizard
		);
	}
}
