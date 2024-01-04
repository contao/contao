<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Provide methods to handle check boxes.
 *
 * @property array   $options
 * @property array   $unknownOption
 * @property boolean $multiple
 * @property boolean $collapseUncheckedGroups
 */
class CheckBox extends Widget
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
		$arrOptions = array();

		if (!$this->multiple && \count($this->arrOptions) > 1)
		{
			$this->arrOptions = array($this->arrOptions[0]);
		}

		// The "required" attribute only makes sense for single checkboxes
		if ($this->mandatory && !$this->multiple)
		{
			$this->arrAttributes['required'] = 'required';
		}

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$state = $objSessionBag->get('checkbox_groups');

		// Toggle the checkbox group
		if (Input::get('cbc'))
		{
			$state[Input::get('cbc')] = (isset($state[Input::get('cbc')]) && $state[Input::get('cbc')] == 1) ? 0 : 1;
			$objSessionBag->set('checkbox_groups', $state);
			$this->redirect(preg_replace('/(&(amp;)?|\?)cbc=[^& ]*/i', '', Environment::get('requestUri')));
		}

		$blnFirst = true;
		$blnCheckAll = true;
		$arrAllOptions = $this->arrOptions;

		// Add unknown options, so they are not lost when saving the record (see #920)
		if (\is_array($this->unknownOption))
		{
			foreach ($this->unknownOption as $val)
			{
				$arrAllOptions[] = array('value' => $val, 'label' => sprintf($GLOBALS['TL_LANG']['MSC']['unknownOption'], $val));
			}
		}

		foreach ($arrAllOptions as $i=>$arrOption)
		{
			// Single dimension array
			if (is_numeric($i))
			{
				$arrOptions[] = $this->generateCheckbox($arrOption, $i);
				continue;
			}

			$id = 'cbc_' . $this->strId . '_' . StringUtil::standardize($i);

			$class = 'foldable';
			$display = 'none';

			$blnIsOpen = ($state[$id] ?? null) || ($this->collapseUncheckedGroups && $blnFirst && empty($this->varValue));

			if (!$blnIsOpen && $this->collapseUncheckedGroups && !isset($state[$id]))
			{
				foreach ($arrOption as $v)
				{
					if ($this->isChecked($v))
					{
						$blnIsOpen = true;
						break;
					}
				}
			}

			if ($blnIsOpen)
			{
				$class = 'foldable foldable--open';
				$display = 'block';
			}

			$arrOptions[] = '<div class="checkbox_toggler' . ($blnFirst ? '_first' : '') . '"><a href="' . Backend::addToUrl('cbc=' . $id) . '" class="' . $class . '" data-action="contao--scroll-offset#store" onclick="AjaxRequest.toggleCheckboxGroup(this,\'' . $id . '\');return false">' . Image::getHtml('chevron-right.svg') . '</a>' . $i . '</div><fieldset id="' . $id . '" class="tl_checkbox_container checkbox_options" style="display:' . $display . '"><input type="checkbox" id="check_all_' . $id . '" class="tl_checkbox" onclick="Backend.toggleCheckboxGroup(this, \'' . $id . '\')"> <label for="check_all_' . $id . '" class="check-all"><em>' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</em></label>';

			// Multidimensional array
			foreach ($arrOption as $k=>$v)
			{
				$arrOptions[] = $this->generateCheckbox($v, StringUtil::standardize($i) . '_' . $k);
			}

			$arrOptions[] = '</fieldset>';
			$blnFirst = false;
			$blnCheckAll = false;
		}

		// Add a "no entries found" message if there are no options
		if (empty($arrOptions))
		{
			$arrOptions[]= '<p class="tl_noopt">' . $GLOBALS['TL_LANG']['MSC']['noResult'] . '</p>';
			$blnCheckAll = false;
		}

		if ($this->multiple)
		{
			return sprintf(
				'<fieldset id="ctrl_%s" class="tl_checkbox_container%s"><legend>%s%s%s%s</legend><input type="hidden" name="%s" value="">%s%s</fieldset>%s',
				$this->strId,
				$this->strClass ? ' ' . $this->strClass : '',
				$this->mandatory ? '<span class="invisible">' . $GLOBALS['TL_LANG']['MSC']['mandatory'] . ' </span>' : '',
				$this->strLabel,
				$this->mandatory ? '<span class="mandatory">*</span>' : '',
				$this->xlabel,
				$this->strName,
				$blnCheckAll ? '<input type="checkbox" id="check_all_' . $this->strId . '" class="tl_checkbox" onclick="Backend.toggleCheckboxGroup(this,\'ctrl_' . $this->strId . '\')' . ($this->onclick ? ';' . $this->onclick : '') . '"> <label for="check_all_' . $this->strId . '" class="check-all"><em>' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</em></label><br>' : '',
				str_replace('<br></fieldset><br>', '</fieldset>', implode('<br>', $arrOptions)),
				$this->wizard
			);
		}

		return sprintf(
			'<div id="ctrl_%s" class="tl_checkbox_single_container%s"><input type="hidden" name="%s" value="">%s</div>%s',
			$this->strId,
			$this->strClass ? ' ' . $this->strClass : '',
			$this->strName,
			str_replace('<br></div><br>', '</div>', implode('<br>', $arrOptions)),
			$this->wizard
		);
	}

	/**
	 * Generate a checkbox and return it as string
	 *
	 * @param array   $arrOption
	 * @param integer $i
	 *
	 * @return string
	 */
	protected function generateCheckbox($arrOption, $i)
	{
		return sprintf(
			'<input type="checkbox" name="%s" id="opt_%s" class="tl_checkbox" value="%s"%s%s onfocus="Backend.getScrollOffset()"> <label for="opt_%s">%s%s%s</label>%s',
			$this->strName . ($this->multiple ? '[]' : ''),
			$this->strId . '_' . $i,
			$this->multiple ? self::specialcharsValue($arrOption['value']) : 1,
			$this->isChecked($arrOption),
			$this->getAttributes(),
			$this->strId . '_' . $i,
			$this->mandatory && !$this->multiple ? '<span class="invisible">' . $GLOBALS['TL_LANG']['MSC']['mandatory'] . ' </span>' : '',
			$arrOption['label'] ?? null,
			$this->mandatory && !$this->multiple ? '<span class="mandatory">*</span>' : '',
			!$this->multiple ? $this->xlabel : ''
		);
	}
}
