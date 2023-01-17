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
 * Provide methods to handle modules of a page layout.
 */
class ModuleWizard extends Widget
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
	 * Check if there is a module without a column
	 */
	public function validate()
	{
		$varValue = $this->getPost($this->strName);

		foreach ($varValue as $v)
		{
			if (empty($v['col']))
			{
				$this->addError($GLOBALS['TL_LANG']['ERR']['moduleWithoutColumn']);
			}
		}

		parent::validate();
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$this->import(Database::class, 'Database');

		$arrButtons = array('edit', 'copy', 'delete', 'enable', 'drag');

		// Get all modules of the current theme
		$objModules = $this->Database->prepare("SELECT id, name, type FROM tl_module WHERE pid=(SELECT pid FROM " . $this->strTable . " WHERE id=?) ORDER BY name")
									 ->execute($this->currentRecord);

		// Add the articles module
		$modules[] = array('id'=>0, 'name'=>$GLOBALS['TL_LANG']['MOD']['article'][0], 'type'=>'article');

		if ($objModules->numRows)
		{
			$modules = array_merge($modules, $objModules->fetchAllAssoc());
		}

		$GLOBALS['TL_LANG']['FMD']['article'] = $GLOBALS['TL_LANG']['MOD']['article'];

		// Add the module type (see #3835)
		foreach ($modules as $k=>$v)
		{
			if (isset($GLOBALS['TL_LANG']['FMD'][$v['type']][0]))
			{
				$v['type'] = $GLOBALS['TL_LANG']['FMD'][$v['type']][0];
			}

			$modules[$k] = $v;
		}

		$objRow = $this->Database->prepare("SELECT * FROM " . $this->strTable . " WHERE id=?")
								 ->limit(1)
								 ->execute($this->currentRecord);

		$cols = array('main');

		if (\in_array($objRow->rows, array('2rwh', '3rw')))
		{
			$cols[] = 'header';
		}

		if (\in_array($objRow->cols, array('2cll', '3cl')))
		{
			$cols[] = 'left';
		}

		if (\in_array($objRow->cols, array('2clr', '3cl')))
		{
			$cols[] = 'right';
		}

		if (\in_array($objRow->rows, array('2rwf', '3rw')))
		{
			$cols[] = 'footer';
		}

		$positions = array();

		// Add custom layout sections
		if ($objRow->sections)
		{
			$arrSections = StringUtil::deserialize($objRow->sections);

			if (!empty($arrSections) && \is_array($arrSections))
			{
				foreach ($arrSections as $v)
				{
					if (!empty($v['id']))
					{
						$cols[] = $v['id'];
						$positions[$v['id']] = $v['position'];
					}
				}
			}
		}

		$cols = Backend::convertLayoutSectionIdsToAssociativeArray($cols);

		// Get the new value
		if (Input::post('FORM_SUBMIT') == $this->strTable)
		{
			$this->varValue = Input::post($this->strId);
		}

		// Make sure there is at least an empty array
		if (!\is_array($this->varValue) || !$this->varValue[0])
		{
			$this->varValue = array(array('mod'=>0, 'col'=>'main'));
		}
		else
		{
			// Initialize the sorting order
			$arrCols = array
			(
				'top' => array(),
				'header' => array(),
				'before' => array(),
				'left' => array(),
				'right' => array(),
				'main' => array(),
				'after' => array(),
				'footer' => array(),
				'bottom' => array(),
				'manual' => array()
			);

			foreach ($this->varValue as $v)
			{
				$key = $positions[$v['col']] ?? $v['col'];

				$arrCols[$key][] = $v;
			}

			$this->varValue = array_merge(...array_values($arrCols));
		}

		// Add the label and the return wizard
		$return = '<table id="ctrl_' . $this->strId . '" class="tl_modulewizard">
  <thead>
  <tr>
    <th>' . $GLOBALS['TL_LANG']['MSC']['mw_module'] . '</th>
    <th>' . $GLOBALS['TL_LANG']['MSC']['mw_column'] . '</th>
    <th></th>
  </tr>
  </thead>
  <tbody class="sortable">';

		// Add the input fields
		for ($i=0, $c=\count($this->varValue); $i<$c; $i++)
		{
			$options = '';

			// Add modules
			foreach ($modules as $v)
			{
				$options .= '<option value="' . self::specialcharsValue($v['id']) . '"' . static::optionSelected($v['id'], $this->varValue[$i]['mod'] ?? null) . '>' . $v['name'] . ' [' . $v['type'] . ']</option>';
			}

			$return .= '
  <tr>
    <td><select name="' . $this->strId . '[' . $i . '][mod]" class="tl_select tl_chosen" onfocus="Backend.getScrollOffset()" onchange="Backend.updateModuleLink(this)">' . $options . '</select></td>';

			$options = '<option value="">-</option>';

			// Add columns
			foreach ($cols as $k=>$v)
			{
				$options .= '<option value="' . self::specialcharsValue($k) . '"' . static::optionSelected($k, $this->varValue[$i]['col'] ?? null) . '>' . $v . '</option>';
			}

			$return .= '
    <td><select name="' . $this->strId . '[' . $i . '][col]" class="tl_select_column" onfocus="Backend.getScrollOffset()">' . $options . '</select></td>
    <td>';

			// Add buttons
			foreach ($arrButtons as $button)
			{
				if ($button == 'edit')
				{
					if (($this->varValue[$i]['mod'] ?? null) > 0)
					{
						$href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>($this->varValue[$i]['mod'] ?? null), 'popup'=>'1')));
						$return .= ' <a href="' . $href . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['tl_layout']['edit_module']) . '" class="module_link" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['tl_layout']['edit_module'])) . '\',\'url\':this.href});return false">' . Image::getHtml('edit.svg') . '</a>';
					}
					else
					{
						$return .= ' ' . Image::getHtml('edit_.svg');
					}
				}
				elseif ($button == 'drag')
				{
					$return .= ' <button type="button" class="drag-handle" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['move']) . '" aria-hidden="true">' . Image::getHtml('drag.svg') . '</button>';
				}
				elseif ($button == 'enable')
				{
					$return .= ' <input name="' . $this->strId . '[' . $i . '][enable]" type="checkbox" class="tl_checkbox mw_enable" value="1" tabindex="-1" onfocus="Backend.getScrollOffset()"' . (($this->varValue[$i]['enable'] ?? null) ? ' checked' : '') . '><button type="button" data-command="enable" class="mw_enable" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['mw_enable']) . '">' . Image::getHtml((($this->varValue[$i]['enable'] ?? null) ? 'visible.svg' : 'invisible.svg')) . '</button>';
				}
				else
				{
					$return .= ' <button type="button" data-command="' . $button . '" title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['mw_' . $button]) . '">' . Image::getHtml($button . '.svg') . '</button>';
				}
			}

			$return .= '</td>
  </tr>';
		}

		return $return . '
  </tbody>
  </table>
  <script>Backend.moduleWizard("ctrl_' . $this->strId . '")</script>';
	}
}
