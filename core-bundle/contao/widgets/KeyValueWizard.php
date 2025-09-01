<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

trigger_deprecation('contao/core-bundle', '5.6', 'Using "Contao\KeyValueWizard" is deprecated and will no longer work in Contao 6. Use the RowWizard instead.');

/**
 * Provide methods to handle key value pairs.
 *
 * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
 *             use the rowWizard instead.
 *
 * @property integer $maxlength
 */
class KeyValueWizard extends Widget
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
		if ($strKey == 'maxlength')
		{
			if ($varValue > 0)
			{
				$this->arrAttributes['maxlength'] = $varValue;
			}
		}
		else
		{
			parent::__set($strKey, $varValue);
		}
	}

	/**
	 * Validate the input and set the value
	 */
	public function validate()
	{
		$mandatory = $this->mandatory;
		$options = $this->getPost($this->strName);

		// Check keys only (values can be empty)
		if (\is_array($options))
		{
			foreach ($options as $key=>$option)
			{
				// Unset empty rows
				if (!$this->allowEmptyKeys && trim($option['key']) === '')
				{
					unset($options[$key]);
					continue;
				}

				$options[$key]['key'] = trim($option['key']);
				$options[$key]['value'] = trim($option['value']);

				if ($options[$key]['key'] !== '' || ($this->allowEmptyKeys && $options[$key]['value'] !== ''))
				{
					$this->mandatory = false;
				}
			}
		}

		$options = array_values($options);
		$varInput = $this->validator($options);

		if (!$this->hasErrors())
		{
			$this->varValue = $varInput;
		}

		// Reset the property
		if ($mandatory)
		{
			$this->mandatory = true;
		}
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		// Make sure there is at least an empty array
		if (!\is_array($this->varValue) || empty($this->varValue[0]))
		{
			$this->varValue = array(array(''));
		}

		return System::getContainer()->get('twig')->render('@Contao/backend/widget/key_value_wizard.html.twig', array(
			'id' => $this->strId,
			'keyLabel' => $this->keyLabel,
			'valueLabel' => $this->valueLabel,
			'rows' => $this->varValue,
		));
	}
}
