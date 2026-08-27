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
 * Class FormRadio
 *
 * @property boolean $mandatory
 * @property array   $options
 */
class FormRadio extends Widget
{
	/**
	 * Submit user input
	 *
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_radio';

	/**
	 * Error message
	 *
	 * @var string
	 */
	protected $strError = '';

	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-radio';

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
				$this->arrOptions = StringUtil::deserialize($varValue, true);
				break;

			case 'rgxp':
			case 'minlength':
			case 'maxlength':
			case 'minval':
			case 'maxval':
				// Ignore
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}

	/**
	 * Return a parameter
	 *
	 * @param string $strKey The parameter name
	 *
	 * @return mixed The parameter value
	 */
	public function __get($strKey)
	{
		if ($strKey == 'options')
		{
			return $this->arrOptions;
		}

		return parent::__get($strKey);
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
	 * Generate the options
	 *
	 * @return array The options array
	 */
	protected function getOptions()
	{
		$arrOptions = array();
		$blnHasGroups = false;

		foreach ($this->arrOptions as $i=>$arrOption)
		{
			if ($arrOption['group'] ?? null)
			{
				if ($blnHasGroups)
				{
					$arrOptions[] = array
					(
						'type' => 'group_end'
					);
				}

				$arrOptions[] = array
				(
					'type'  => 'group_start',
					'label' => StringUtil::specialchars($arrOption['label'] ?? '')
				);

				$blnHasGroups = true;
			}
			else
			{
				$arrOptions[] = array_replace
				(
					$arrOption,
					array
					(
						'type'       => 'option',
						'name'       => $this->strName,
						'id'         => $this->strId . '_' . $i,
						'value'      => $arrOption['value'] ?? null,
						'checked'    => $this->isChecked($arrOption),
						'attributes' => $this->getAttributes(),
						'label'      => $arrOption['label'] ?? null
					)
				);
			}
		}

		if ($blnHasGroups)
		{
			$arrOptions[] = array
			(
				'type' => 'group_end'
			);
		}

		return $arrOptions;
	}
}
