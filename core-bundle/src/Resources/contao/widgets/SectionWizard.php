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
 * Provide methods to handle sections of a page layout.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class SectionWizard extends \Widget
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
	 * Standardize the ID
	 *
	 * @param mixed $varInput
	 *
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		if (isset($varInput['id']))
		{
			$varInput['id'] = \StringUtil::standardize($varInput['id']);
		}

		return parent::validator($varInput);
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

		// Add the label and the return wizard
		$return = '<table id="ctrl_'.$this->strId.'" class="tl_sectionwizard">
  <thead>
  <tr>
    <th>'.$GLOBALS['TL_LANG']['MSC']['sw_title'].'</th>
    <th>'.$GLOBALS['TL_LANG']['MSC']['sw_id'].'</th>
    <th>'.$GLOBALS['TL_LANG']['MSC']['sw_template'].'</th>
    <th>'.$GLOBALS['TL_LANG']['MSC']['sw_position'].'</th>
    <th></th>
  </tr>
  </thead>
  <tbody class="sortable">';

		// Add the input fields
		for ($i=0, $c=count($this->varValue); $i<$c; $i++)
		{
			$return .= '
    <tr>
      <td><input type="text" name="'.$this->strId.'['.$i.'][title]" id="'.$this->strId.'_title_'.$i.'" class="tl_text" value="'.\StringUtil::specialchars($this->varValue[$i]['title']).'"></td>
      <td><input type="text" name="'.$this->strId.'['.$i.'][id]" id="'.$this->strId.'_id_'.$i.'" class="tl_text" value="'.\StringUtil::specialchars($this->varValue[$i]['id']).'"></td>';

			$options = '';

			// Add the template
			foreach (\Template::getTemplateGroup('block_section_') as $k=>$v)
			{
				$options .= '<option value="'.\StringUtil::specialchars($k).'"'.static::optionSelected($k, $this->varValue[$i]['template']).'>'.$v.'</option>';
			}

			$return .= '
    <td><select name="'.$this->strId.'['.$i.'][template]" class="tl_select" onfocus="Backend.getScrollOffset()">'.$options.'</select></td>';

			$options = '';

			// Add the positions
			foreach (array('top', 'before', 'main', 'after', 'bottom', 'manual') as $v)
			{
				$options .= '<option value="'.\StringUtil::specialchars($v).'"'.static::optionSelected($v, $this->varValue[$i]['position']).'>'.$GLOBALS['TL_LANG']['SECTIONS'][$v].'</option>';
			}

			$return .= '
    <td><select name="'.$this->strId.'['.$i.'][position]" class="tl_select" onfocus="Backend.getScrollOffset()">'.$options.'</select></td>
    <td>';

			// Add the buttons
			foreach ($arrButtons as $button)
			{
				if ($button == 'drag')
				{
					$return .= ' <button type="button" class="drag-handle" title="' . \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['move']) . '">' . \Image::getHtml('drag.svg') . '</button>';
				}
				else
				{
					$return .= ' <button type="button" data-command="' . $button . '" title="' . \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['mw_'.$button]) . '">' . \Image::getHtml($button.'.svg') . '</button>';
				}
			}

			$return .= '</td>
  </tr>';
		}

		return $return.'
  </tbody>
  </table>
  <script>Backend.sectionWizard("ctrl_'.$this->strId.'")</script>';
	}
}
