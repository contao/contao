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
 * Provide methods to handle radio buttons.
 *
 * @property boolean $mandatory
 * @property array   $options
 * @property array   $unknownOption
 */
class RadioButton extends Widget
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
	protected $strTemplate = 'be_widget_rdo';

	/**
	 * @param array $arrAttributes
	 */
	public function __construct($arrAttributes=null)
	{
		parent::__construct($arrAttributes);

		$this->preserveTags = true;
		$this->decodeEntities = true;
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

			case 'options':
				$this->arrOptions = StringUtil::deserialize($varValue);
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
		$arrOptions = $this->arrOptions;

		if (isset($this->unknownOption[0]))
		{
			$this->arrOptions[] = array('value'=>$this->unknownOption[0]);
		}

		$blnIsValid = parent::isValidOption($varInput);
		$this->arrOptions = $arrOptions;

		return $blnIsValid;
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$arrOptions = array();
		$arrAllOptions = $this->arrOptions;

		// Add an unknown option, so it is not lost when saving the record (see #920)
		if (isset($this->unknownOption[0]))
		{
			$arrAllOptions[] = array('value' => $this->unknownOption[0], 'label' => sprintf($GLOBALS['TL_LANG']['MSC']['unknownOption'], $this->unknownOption[0]));
		}

		foreach ($arrAllOptions as $i=>$arrOption)
		{
			$arrOptions[] = sprintf(
				'<input type="radio" name="%s" id="opt_%s" class="tl_radio" value="%s"%s%s data-action="focus->contao--scroll-offset#store"> <label for="opt_%s">%s</label>',
				$this->strName,
				$this->strId . '_' . $i,
				self::specialcharsValue($arrOption['value'] ?? ''),
				$this->isChecked($arrOption),
				$this->getAttributes(),
				$this->strId . '_' . $i,
				$arrOption['label'] ?? null
			);
		}

		// Add a "no entries found" message if there are no options
		if (empty($arrOptions))
		{
			$arrOptions[]= '<p class="tl_noopt">' . $GLOBALS['TL_LANG']['MSC']['noResult'] . '</p>';
		}

		return sprintf(
			'<fieldset id="ctrl_%s" class="tl_radio_container%s"><legend>%s%s%s%s</legend>%s</fieldset>%s',
			$this->strId,
			$this->strClass ? ' ' . $this->strClass : '',
			$this->mandatory ? '<span class="invisible">' . $GLOBALS['TL_LANG']['MSC']['mandatory'] . ' </span>' : '',
			$this->strLabel,
			$this->mandatory ? '<span class="mandatory">*</span>' : '',
			$this->xlabel,
			implode('<br>', $arrOptions),
			$this->wizard
		);
	}
}
