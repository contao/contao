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
 * Provide methods to handle form field options.
 */
class OptionWizard extends Widget
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
	 * Validate the input and set the value
	 */
	public function validate()
	{
		$mandatory = $this->mandatory;
		$options = $this->getPost($this->strName);

		// Check labels only (values can be empty)
		if (\is_array($options))
		{
			foreach ($options as $key=>$option)
			{
				// Unset empty rows
				if (trim($option['label']) === '')
				{
					unset($options[$key]);
					continue;
				}

				$options[$key]['label'] = trim($option['label']);
				$options[$key]['value'] = trim($option['value']);

				if ($options[$key]['label'])
				{
					$this->mandatory = false;
				}

				// Strip double quotes (see #6919)
				if ($options[$key]['value'])
				{
					$options[$key]['value'] = str_replace('"', '', $options[$key]['value']);
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
		$return = '<table id="ctrl_' . $this->strId . '" class="tl_optionwizard">
  <thead>
    <tr>
      <th>' . $GLOBALS['TL_LANG']['MSC']['ow_value'] . '</th>
      <th>' . $GLOBALS['TL_LANG']['MSC']['ow_label'] . '</th>
      <th></th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody class="sortable">';

		// Add fields
		for ($i=0, $c=\count($this->varValue); $i<$c; $i++)
		{
			$return .= '
    <tr>
      <td><input type="text" name="' . $this->strId . '[' . $i . '][value]" id="' . $this->strId . '_value_' . $i . '" class="tl_text" value="' . self::specialcharsValue($this->varValue[$i]['value'] ?? '') . '"></td>
      <td><input type="text" name="' . $this->strId . '[' . $i . '][label]" id="' . $this->strId . '_label_' . $i . '" class="tl_text" value="' . self::specialcharsValue($this->varValue[$i]['label'] ?? '') . '"></td>
      <td><input type="checkbox" name="' . $this->strId . '[' . $i . '][default]" id="' . $this->strId . '_default_' . $i . '" class="fw_checkbox" value="1"' . (($this->varValue[$i]['default'] ?? null) ? ' checked="checked"' : '') . '> <label for="' . $this->strId . '_default_' . $i . '">' . $GLOBALS['TL_LANG']['MSC']['ow_default'] . '</label></td>
      <td><input type="checkbox" name="' . $this->strId . '[' . $i . '][group]" id="' . $this->strId . '_group_' . $i . '" class="fw_checkbox" value="1"' . (($this->varValue[$i]['group'] ?? null) ? ' checked="checked"' : '') . '> <label for="' . $this->strId . '_group_' . $i . '">' . $GLOBALS['TL_LANG']['MSC']['ow_group'] . '</label></td>';

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
  <script>Backend.optionsWizard("ctrl_' . $this->strId . '")</script>';
	}
}
