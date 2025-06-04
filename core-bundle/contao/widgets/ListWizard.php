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
 * Provide methods to handle list items.
 *
 * @property integer $maxlength
 */
class ListWizard extends Widget
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
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$arrButtons = array('copy', 'delete');

		// Make sure there is at least an empty array
		if (empty($this->varValue) || !\is_array($this->varValue))
		{
			$this->varValue = array('');
		}

		$return = '<ul id="ctrl_' . $this->strId . '" class="tl_listwizard" data-controller="contao--sortable" data-contao--sortable-drag-handle-value=".drag-handle">';

		// Add input fields
		for ($i=0, $c=\count($this->varValue); $i<$c; $i++)
		{
			$return .= '
    <li>
		<button type="button" class="drag-handle" aria-hidden="true">' . Image::getHtml('drag.svg', $GLOBALS['TL_LANG']['MSC']['move']) . '</button>
    	<input type="text" name="' . $this->strId . '[]" class="tl_text" value="' . self::specialcharsValue($this->varValue[$i]) . '"' . $this->getAttributes() . '> ';

			// Add buttons
			foreach ($arrButtons as $button)
			{
				$return .= ' <button type="button" data-command="' . $button . '">' . Image::getHtml($button . '.svg', $GLOBALS['TL_LANG']['MSC']['lw_' . $button]) . '</button>';
			}

			$return .= '</li>';
		}

		return $return . '
  </ul>
  <script>Backend.listWizard("ctrl_' . $this->strId . '")</script>';
	}
}
