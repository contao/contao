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
 * Provide methods to handle input field "picker".
 */
class Picker extends Widget
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
	 * Load the database object
	 *
	 * @param array $arrAttributes
	 */
	public function __construct($arrAttributes=null)
	{
		$this->import(Database::class, 'Database');
		parent::__construct($arrAttributes);

		// Prepare the order field
		if ($this->orderField)
		{
			$this->strOrderId = $this->orderField . str_replace($this->strField, '', $this->strId);
			$this->strOrderName = $this->orderField . str_replace($this->strField, '', $this->strName);

			// Retrieve the order value
			$objRow = $this->Database->prepare("SELECT " . Database::quoteIdentifier($this->orderField) . " FROM " . $this->strTable . " WHERE id=?")
									 ->limit(1)
									 ->execute($this->activeRecord->id);

			$tmp = StringUtil::deserialize($objRow->{$this->orderField});
			$this->{$this->orderField} = (!empty($tmp) && \is_array($tmp)) ? array_filter($tmp) : array();
		}
	}

	/**
	 * Return an array if the "multiple" attribute is set
	 *
	 * @param mixed $varInput
	 *
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		if ($this->hasErrors())
		{
			return '';
		}

		// Store the order value
		if ($this->orderField)
		{
			$arrNew = array();

			if ($order = Input::post($this->strOrderName))
			{
				$arrNew = explode(',', $order);
			}

			// Only proceed if the value has changed
			if ($arrNew !== $this->{$this->orderField})
			{
				$this->Database->prepare("UPDATE " . $this->strTable . " SET tstamp=?, " . Database::quoteIdentifier($this->orderField) . "=? WHERE id=?")
							   ->execute(time(), serialize($arrNew), $this->activeRecord->id);

				$this->objDca->createNewVersion = true; // see #6285
			}
		}

		// Return the value as usual
		if (!$varInput)
		{
			if ($this->mandatory)
			{
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
			}

			return '';
		}

		if (strpos($varInput, ',') === false)
		{
			return $this->multiple ? array((int) $varInput) : (int) $varInput;
		}

		$arrValue = array_filter(explode(',', $varInput));

		return $this->multiple ? $arrValue : $arrValue[0];
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$strRelatedTable = $this->getRelatedTable();
		$strContext = $this->context ?: 'dc.' . $strRelatedTable;
		$blnHasOrder = $this->orderField && \is_array($this->{$this->orderField});
		$arrValues = $this->generateValues($blnHasOrder);
		$arrSet = array_keys($arrValues);

		$return = '<input type="hidden" name="' . $this->strName . '" id="ctrl_' . $this->strId . '" value="' . implode(',', $arrSet) . '"' . ($this->onchange ? ' onchange="' . $this->onchange . '"' : '') . '>' . ($blnHasOrder ? '
  <input type="hidden" name="' . $this->strOrderName . '" id="ctrl_' . $this->strOrderId . '" value="' . implode(',', $this->{$this->orderField}) . '">' : '') . '
  <div class="selector_container">' . (($blnHasOrder && \count($arrValues) > 1) ? '
    <p class="sort_hint">' . $GLOBALS['TL_LANG']['MSC']['dragItemsHint'] . '</p>' : '');

		if (($GLOBALS['TL_DCA'][$strRelatedTable]['list']['label']['showColumns'] ?? null) && !empty($arrValues))
		{
			System::loadLanguageFile($strRelatedTable);

			$showFields = $GLOBALS['TL_DCA'][$strRelatedTable]['list']['label']['fields'];

			$return .= '
<table class="tl_listing showColumns' . ($blnHasOrder ? ' sortable' : '') . '">
<thead>
  <tr>';

			foreach ($showFields as $f)
			{
				if (strpos($f, ':') !== false)
				{
					list($f) = explode(':', $f, 2);
				}

				$return .= '
    <th class="tl_folder_tlist col_' . $f . '">' . (\is_array($GLOBALS['TL_DCA'][$strRelatedTable]['fields'][$f]['label']) ? $GLOBALS['TL_DCA'][$strRelatedTable]['fields'][$f]['label'][0] : $GLOBALS['TL_DCA'][$strRelatedTable]['fields'][$f]['label']) . '</th>';
			}

			$return .= '
  </tr>
</thead>
<tbody id="sort_' . $this->strId . '">';

			foreach ($arrValues as $k => $row)
			{
				$return .= '
  <tr data-id="' . $k . '">';

				foreach ($row as $j=>$arg)
				{
					$field = $GLOBALS['TL_DCA'][$strRelatedTable]['list']['label']['fields'][$j];

					$return .= '
    <td class="tl_file_list col_' . $field . '">' . ($arg ?: '-') . '</td>';
				}

				$return .= '
  </tr>';
			}

			$return .= '
</tbody>
</table>';
		}
		else
		{
			$return .= '
    <ul id="sort_' . $this->strId . '" class="' . ($blnHasOrder ? 'sortable' : '') . '">';

			foreach ($arrValues as $k=>$v)
			{
				$return .= '<li data-id="' . $k . '">' . $v . '</li>';
			}

			$return .= '</ul>';
		}

		if (!System::getContainer()->get('contao.picker.builder')->supportsContext($strContext))
		{
			$return .= '
	<p><button class="tl_submit" disabled>' . $GLOBALS['TL_LANG']['MSC']['changeSelection'] . '</button></p>';
		}
		else
		{
			$extras = $this->getPickerUrlExtras($arrValues);

			$return .= '
    <p><a href="' . ampersand(System::getContainer()->get('contao.picker.builder')->getUrl($strContext, $extras)) . '" class="tl_submit" id="picker_' . $this->strName . '">' . $GLOBALS['TL_LANG']['MSC']['changeSelection'] . '</a></p>
    <script>
      $("picker_' . $this->strName . '").addEvent("click", function(e) {
        e.preventDefault();
        Backend.openModalSelector({
          "id": "tl_listing",
          "title": ' . json_encode($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][0]) . ',
          "url": this.href + document.getElementById("ctrl_' . $this->strId . '").value,
          "callback": function(table, value) {
            new Request.Contao({
              evalScripts: false,
              onSuccess: function(txt, json) {
                $("ctrl_' . $this->strId . '").getParent("div").set("html", json.content);
                json.javascript && Browser.exec(json.javascript);
                var evt = document.createEvent("HTMLEvents");
                evt.initEvent("change", true, true);
                $("ctrl_' . $this->strId . '").dispatchEvent(evt);
              }
            }).post({"action":"reloadPicker", "name":"' . $this->strName . '", "value":value.join("\t"), "REQUEST_TOKEN":"' . REQUEST_TOKEN . '"});
          }
        });
      });
    </script>' . ($blnHasOrder ? '
    <script>Backend.makeMultiSrcSortable("sort_' . $this->strId . '", "ctrl_' . $this->strOrderId . '", "ctrl_' . $this->strId . '")</script>' : '');
		}

		$return = '<div>' . $return . '</div></div>';

		return $return;
	}

	protected function generateValues($blnHasOrder): array
	{
		$strRelatedTable = $this->getRelatedTable();

		if (!$strRelatedTable)
		{
			return array_combine((array) $this->varValue, (array) $this->varValue);
		}

		Controller::loadDataContainer($strRelatedTable);

		$arrValues = array();

		if (!empty($this->varValue))
		{
			$objRows = $this->Database->execute("SELECT * FROM $strRelatedTable WHERE id IN (" . implode(',', array_map('intval', (array) $this->varValue)) . ")");

			if ($objRows->numRows)
			{
				$dataContainer = DataContainer::getDriverForTable($strRelatedTable);

				$dc = (new \ReflectionClass($dataContainer))->newInstanceWithoutConstructor();
				$dc->table = $strRelatedTable;

				while ($objRows->next())
				{
					$dc->id = $objRows->id;
					$dc->activeRecord = $objRows;

					$arrSet[] = $objRows->id;
					$arrValues[$objRows->id] = $this->renderLabel($objRows->row(), $dc);
				}
			}

			// Apply a custom sort order
			if ($blnHasOrder)
			{
				$arrNew = array();

				foreach ((array) $this->{$this->orderField} as $i)
				{
					if (isset($arrValues[$i]))
					{
						$arrNew[$i] = $arrValues[$i];
						unset($arrValues[$i]);
					}
				}

				if (!empty($arrValues))
				{
					foreach ($arrValues as $k=>$v)
					{
						$arrNew[$k] = $v;
					}
				}

				$arrValues = $arrNew;
				unset($arrNew);
			}
		}

		return $arrValues;
	}

	protected function renderLabel(array $arrRow, DataContainer $dc)
	{
		$mode = $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] ?? 1;

		if ($mode === 4)
		{
			$callback = $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['child_record_callback'];

			if (\is_array($callback))
			{
				$this->import($callback[0]);

				return $this->{$callback[0]}->{$callback[1]}($arrRow);
			}

			if (\is_callable($callback))
			{
				return $callback($arrRow);
			}
		}

		$labelConfig = &$GLOBALS['TL_DCA'][$dc->table]['list']['label'];
		$labelValues = array();

		foreach ($labelConfig['fields'] as $k => $v)
		{
			if (strpos($v, ':') !== false)
			{
				list($strKey, $strTable) = explode(':', $v);
				list($strTable, $strField) = explode('.', $strTable);

				$objRef = $this->Database->prepare("SELECT " . Database::quoteIdentifier($strField) . " FROM " . $strTable . " WHERE id=?")
										 ->limit(1)
										 ->execute($arrRow[$strKey]);

				$labelValues[$k] = $objRef->numRows ? $objRef->$strField : '';
			}
			elseif (\in_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['flag'], array(5, 6, 7, 8, 9, 10)))
			{
				if ($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['rgxp'] == 'date')
				{
					$labelValues[$k] = $arrRow[$v] ? Date::parse(Config::get('dateFormat'), $arrRow[$v]) : '-';
				}
				elseif ($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['rgxp'] == 'time')
				{
					$labelValues[$k] = $arrRow[$v] ? Date::parse(Config::get('timeFormat'), $arrRow[$v]) : '-';
				}
				else
				{
					$labelValues[$k] = $arrRow[$v] ? Date::parse(Config::get('datimFormat'), $arrRow[$v]) : '-';
				}
			}
			elseif ($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['isBoolean'] || ($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['multiple']))
			{
				$labelValues[$k] = $arrRow[$v] ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
			}
			else
			{
				$row_v = StringUtil::deserialize($arrRow[$v]);

				if (\is_array($row_v))
				{
					$args_k = array();

					foreach ($row_v as $option)
					{
						$args_k[] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$option] ?: $option;
					}

					$labelValues[$k] = implode(', ', $args_k);
				}
				elseif (isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]]))
				{
					$labelValues[$k] = \is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]]) ? $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]][0] : $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['reference'][$arrRow[$v]];
				}
				elseif (($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['eval']['isAssociative'] || array_is_assoc($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['options'])) && isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['options'][$arrRow[$v]]))
				{
					$labelValues[$k] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$v]['options'][$arrRow[$v]];
				}
				else
				{
					$labelValues[$k] = $arrRow[$v];
				}
			}
		}

		$label = vsprintf($labelConfig['format'], $labelValues);

		if (\is_array($labelConfig['label_callback']))
		{
			$this->import($labelConfig['label_callback'][0]);

			if (\in_array($mode, array(5, 6)))
			{
				return $this->{$labelConfig['label_callback'][0]}->{$labelConfig['label_callback'][1]}($arrRow, $label, $dc, '', false, null);
			}

			return $this->{$labelConfig['label_callback'][0]}->{$labelConfig['label_callback'][1]}($arrRow, $label, $dc, $labelValues);
		}

		if (\is_callable($labelConfig['label_callback']))
		{
			if (\in_array($mode, array(5, 6)))
			{
				return $labelConfig['label_callback']($arrRow, $label, $dc, '', false, null);
			}

			return $labelConfig['label_callback']($arrRow, $label, $dc, $labelValues);
		}

		return $label ?: $arrRow['id'];
	}

	protected function getRelatedTable(): string
	{
		if (substr($this->context ?? '', 0, 3) === 'dc.')
		{
			return substr($this->context, 3);
		}

		$arrRelations = DcaExtractor::getInstance($this->strTable)->getRelations();

		return (string) $arrRelations[$this->strField]['table'];
	}

	/**
	 * Return the extra parameters for the picker URL
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	protected function getPickerUrlExtras($values = array())
	{
		$extras = array();
		$extras['fieldType'] = $this->multiple ? 'checkbox' : 'radio';
		$extras['source'] = $this->strTable . '.' . $this->currentRecord;

		return $extras;
	}
}
