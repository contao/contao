<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Provide methods to handle key value pairs.
 *
 * @property integer $maxlength
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class KeyValueWizard extends \Widget
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

			default:
				parent::__set($strKey, $varValue);
				break;
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
		if (is_array($options))
		{
			foreach ($options as $key=>$option)
			{
				// Unset empty rows
				if ($option['key'] == '')
				{
					unset($options[$key]);
					continue;
				}

				$options[$key]['key'] = trim($option['key']);
				$options[$key]['value'] = trim($option['value']);

				if ($options[$key]['key'] != '')
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
		if (!is_array($this->varValue) || !$this->varValue[0])
		{
			$this->varValue = array(array(''));
		}

		// Initialize the tab index
		if (!\Cache::has('tabindex'))
		{
			\Cache::set('tabindex', 1);
		}

		$tabindex = \Cache::get('tabindex');

		// Begin the table
		$return = '<table class="tl_optionwizard" id="ctrl_'.$this->strId.'">
  <thead>
    <tr>
      <th>'.$GLOBALS['TL_LANG']['MSC']['ow_key'].'</th>
      <th>'.$GLOBALS['TL_LANG']['MSC']['ow_value'].'</th>
      <th style="min-width:54px"></th>
    </tr>
  </thead>
  <tbody class="sortable" data-tabindex="'.$tabindex.'">';

		// Add fields
		for ($i=0, $c=count($this->varValue); $i<$c; $i++)
		{
			$return .= '
    <tr>
      <td><input type="text" name="'.$this->strId.'['.$i.'][key]" id="'.$this->strId.'_key_'.$i.'" class="tl_text" tabindex="'.$tabindex++.'" value="'.\StringUtil::specialchars($this->varValue[$i]['key']).'"'.$this->getAttributes().'></td>
      <td><input type="text" name="'.$this->strId.'['.$i.'][value]" id="'.$this->strId.'_value_'.$i.'" class="tl_text" tabindex="'.$tabindex++.'" value="'.\StringUtil::specialchars($this->varValue[$i]['value']).'"'.$this->getAttributes().'></td>';

			// Add row buttons
			$return .= '
      <td style="white-space:nowrap;padding-left:3px">';

			foreach ($arrButtons as $button)
			{
				if ($button == 'drag')
				{
					$return .= ' ' . \Image::getHtml('drag.svg', '', 'class="drag-handle" title="' . \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['move']) . '"');
				}
				else
				{
					$return .= ' ' . \Image::getHtml($button.'.svg', '', 'title="' . \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['ow_'.$button]) . '" onclick="Backend.keyValueWizard(this,\''.$button.'\',\'ctrl_'.$this->strId.'\');return false"');
				}
			}

			$return .= '</td>
    </tr>';
		}

		// Store the tab index
		\Cache::set('tabindex', $tabindex);

		return $return.'
  </tbody>
  </table>';
	}
}
