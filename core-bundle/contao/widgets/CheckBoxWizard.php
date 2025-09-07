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
			$this->arrOptions = StringUtil::deserialize($varValue, true);
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
					$arrOption['checked'] = true;
					$arrOptions[$intPos] = $arrOption;
					unset($arrTemp[$i]);
				}
			}

			ksort($arrOptions);
			$this->arrOptions = array_merge($arrOptions, $arrTemp);
		}

		$arrOptions = array();
		$arrAllOptions = $this->arrOptions;

		// Add unknown options, so they are not lost when saving the record (see #920)
		if (\is_array($this->unknownOption))
		{
			foreach ($this->unknownOption as $val)
			{
				$arrAllOptions[] = array('value' => $val, 'label' => \sprintf($GLOBALS['TL_LANG']['MSC']['unknownOption'], $val));
			}
		}

		return System::getContainer()->get('twig')->render('@Contao/backend/widget/checkbox_wizard.html.twig', array(
			'id' => $this->strId,
			'class' => $this->strClass,
			'mandatory' => $this->mandatory,
			'label' => $this->strLabel,
			'xlabel' => $this->xlabel,
			'name' => $this->strName,
			'options' => $arrAllOptions,
			'attributes' => $this->getAttributes(),
		));
	}

	/**
	 * Generate a checkbox and return it as string
	 *
	 * @param array   $arrOption
	 * @param integer $i
	 * @param string  $strButtons
	 *
	 * @return string
	 *
	 * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
	 *              use the checkbox_wizard.html.twig template instead.
	 */
	protected function generateCheckbox($arrOption, $i, $strButtons)
	{
		trigger_deprecation('contao/core-bundle', '5.6', 'Using "%s()" is deprecated and will no longer work in Contao 6. Use the checkbox_wizard.html.twig template instead.', __METHOD__);

		return \sprintf(
			'<span><input type="checkbox" name="%s" id="opt_%s" class="tl_checkbox" data-contao--check-all-target="source" value="%s"%s%s data-action="focus->contao--scroll-offset#store"> %s<label for="opt_%s">%s</label></span>',
			$this->strName . ($this->multiple ? '[]' : ''),
			$this->strId . '_' . $i,
			$this->multiple ? self::specialcharsValue($arrOption['value'] ?? '') : 1,
			((\is_array($this->varValue) && \in_array($arrOption['value'] ?? null, $this->varValue)) || $this->varValue == ($arrOption['value'] ?? null)) ? ' checked="checked"' : '',
			$this->getAttributes(),
			$strButtons,
			$this->strId . '_' . $i,
			$arrOption['label'] ?? null
		);
	}
}
