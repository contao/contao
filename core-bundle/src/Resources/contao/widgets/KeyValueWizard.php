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
 * Provide methods to handle key value pairs.
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
				if (trim($option['key']) === '')
				{
					unset($options[$key]);
					continue;
				}

				$options[$key]['key'] = trim($option['key']);
				$options[$key]['value'] = trim($option['value']);

				if ($options[$key]['key'])
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
		$arrButtons = array('copy', 'delete', 'drag');

		// Make sure there is at least an empty array
		if (!\is_array($this->varValue) || empty($this->varValue[0]))
		{
			$this->varValue = array(array(''));
		}

		// Begin the table
		$return = '<table id="ctrl_' . $this->strId . '" class="tl_key_value_wizard">
  <thead>
    <tr>
      <th>' . $GLOBALS['TL_LANG']['MSC']['ow_key'] . '</th>
      <th>' . $GLOBALS['TL_LANG']['MSC']['ow_value'] . '</th>
      <th></th>
    </tr>
  </thead>
  <tbody class="sortable">';

		// Add fields
		for ($i=0, $c=\count($this->varValue); $i<$c; $i++)
		{
			$return .= '
    <tr>
      <td><input type="text" name="' . $this->strId . '[' . $i . '][key]" id="' . $this->strId . '_key_' . $i . '" class="tl_text" value="' . self::specialcharsValue($this->varValue[$i]['key'] ?? '') . '"' . $this->getAttributes() . '></td>
      <td><input type="text" name="' . $this->strId . '[' . $i . '][value]" id="' . $this->strId . '_value_' . $i . '" class="tl_text" value="' . self::specialcharsValue($this->varValue[$i]['value'] ?? '') . '"' . $this->getAttributes() . '></td>';

			// Add row buttons
			$return .= '
      <td>';

			foreach ($arrButtons as $button)
			{
				if ($button == 'drag')
				{
					$return .= ' <button type="button" class="drag-handle" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['move']) . '" aria-hidden="true">' . Image::getHtml('drag.svg') . '</button>';
				}
				else
				{
					$return .= ' <button type="button" data-command="' . $button . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['ow_' . $button]) . '">' . Image::getHtml($button . '.svg') . '</button>';
				}
			}

			$return .= '</td>
    </tr>';
		}

		return $return . '
  </tbody>
  </table>
  <script>Backend.keyValueWizard("ctrl_' . $this->strId . '")</script>';
	}
}
