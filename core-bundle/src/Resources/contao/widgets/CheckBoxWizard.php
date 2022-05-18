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
 * Provide methods to handle sortable checkboxes.
 *
 * @property array   $options
 * @property array   $unknownOption
 * @property boolean $multiple
 */
class CheckBoxWizard extends Widget
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
	protected $strTemplate = 'be_widget_chk';

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
		if ($strKey == 'options')
		{
			$this->arrOptions = StringUtil::deserialize($varValue);
		}
		else
		{
			parent::__set($strKey, $varValue);
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

		if (\is_array($this->unknownOption))
		{
			foreach ($this->unknownOption as $v)
			{
				$this->arrOptions[] = array('value'=>$v);
			}
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
		if (!\is_array($this->varValue))
		{
			$this->varValue = array($this->varValue);
		}

		// Sort options
		if ($this->varValue)
		{
			$arrOptions = array();
			$arrTemp = $this->arrOptions;

			// Move selected and sorted options to the top
			foreach ($this->arrOptions as $i=>$arrOption)
			{
				if (($intPos = array_search($arrOption['value'] ?? null, $this->varValue)) !== false)
				{
					$arrOptions[$intPos] = $arrOption;
					unset($arrTemp[$i]);
				}
			}

			ksort($arrOptions);
			$this->arrOptions = array_merge($arrOptions, $arrTemp);
		}

		$blnCheckAll = true;
		$arrOptions = array();
		$arrAllOptions = $this->arrOptions;

		// Add unknown options, so they are not lost when saving the record (see #920)
		if (\is_array($this->unknownOption))
		{
			foreach ($this->unknownOption as $val)
			{
				$arrAllOptions[] = array('value' => $val, 'label' => sprintf($GLOBALS['TL_LANG']['MSC']['unknownOption'], $val));
			}
		}

		// Generate options and add buttons
		foreach ($arrAllOptions as $i=>$arrOption)
		{
			$arrOptions[] = $this->generateCheckbox($arrOption, $i, '<button type="button" class="drag-handle" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['move']) . '" aria-hidden="true">' . Image::getHtml('drag.svg') . '</button> ');
		}

		// Add a "no entries found" message if there are no options
		if (empty($arrOptions))
		{
			$arrOptions[] = '<p class="tl_noopt">' . $GLOBALS['TL_LANG']['MSC']['noResult'] . '</p>';
			$blnCheckAll = false;
		}

		return sprintf(
			'<fieldset id="ctrl_%s" class="tl_checkbox_container tl_checkbox_wizard%s"><legend>%s%s%s%s</legend><input type="hidden" name="%s" value="">%s<div class="sortable">%s</div></fieldset>%s<script>Backend.checkboxWizard("ctrl_%s")</script>',
			$this->strId,
			($this->strClass ? ' ' . $this->strClass : ''),
			($this->mandatory ? '<span class="invisible">' . $GLOBALS['TL_LANG']['MSC']['mandatory'] . ' </span>' : ''),
			$this->strLabel,
			($this->mandatory ? '<span class="mandatory">*</span>' : ''),
			$this->xlabel,
			$this->strName,
			($blnCheckAll ? '<span class="fixed"><input type="checkbox" id="check_all_' . $this->strId . '" class="tl_checkbox" onclick="Backend.toggleCheckboxGroup(this,\'ctrl_' . $this->strId . '\')"> <label for="check_all_' . $this->strId . '" style="color:#a6a6a6"><em>' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</em></label></span>' : ''),
			implode('', $arrOptions),
			$this->wizard,
			$this->strId
		);
	}

	/**
	 * Generate a checkbox and return it as string
	 *
	 * @param array   $arrOption
	 * @param integer $i
	 * @param string  $strButtons
	 *
	 * @return string
	 */
	protected function generateCheckbox($arrOption, $i, $strButtons)
	{
		return sprintf(
			'<span><input type="checkbox" name="%s" id="opt_%s" class="tl_checkbox" value="%s"%s%s onfocus="Backend.getScrollOffset()"> %s<label for="opt_%s">%s</label></span>',
			$this->strName . ($this->multiple ? '[]' : ''),
			$this->strId . '_' . $i,
			($this->multiple ? self::specialcharsValue($arrOption['value'] ?? '') : 1),
			(((\is_array($this->varValue) && \in_array($arrOption['value'] ?? null, $this->varValue)) || $this->varValue == ($arrOption['value'] ?? null)) ? ' checked="checked"' : ''),
			$this->getAttributes(),
			$strButtons,
			$this->strId . '_' . $i,
			$arrOption['label'] ?? null
		);
	}
}
