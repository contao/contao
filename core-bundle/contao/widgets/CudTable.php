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
 * Provide methods to handle CUD tables.
 */
class CudTable extends Widget
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
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$return = '  <table id="ctrl_defaultCud" class="tl_chmod">
    <tr>
      <th></th>
      <th scope="col">Create</th>
      <th scope="col">Update</th>
      <th scope="col">Delete</th>
    </tr>';

		$tables = array();

		foreach ($this->options as $option)
		{
			$tables[] = $option['value'];
		}

		sort($tables);

		// Build rows for user, group and world
		foreach ($tables as $table)
		{
			$return .= '
    <tr>
      <th scope="row">' . $table . '</th>';

			// Add checkboxes
			for ($j = 1; $j <= 3; ++$j)
			{
				$return .= '
      <td><input type="checkbox" name="' . $table . '[]" value="' . self::specialcharsValue($table . $j) . '"' . $this->getAttributes() . static::optionChecked($table . $j, $this->varValue) . ' data-action="focus->contao--scroll-offset#store"></td>';
			}

			$return .= '
    </tr>';
		}

		return $return . '
  </table>';
	}
}
