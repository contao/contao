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
		if ($this->orderField != '')
		{
			@trigger_error('Using "orderField" for the picker has been deprecated and will no longer work in Contao 5.0. Use "isSortable" instead.', E_USER_DEPRECATED);

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
		if ($this->orderField != '')
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
		if ($varInput == '')
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

		$arrValue = array_map('\intval', array_filter(explode(',', $varInput)));

		return $this->multiple ? $arrValue : $arrValue[0];
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$strContext = $this->context ?: 'dc.' . $this->getRelatedTable();
		$blnHasOrder = ($this->orderField != '' && \is_array($this->{$this->orderField}));
		$arrValues = $this->generateValues($blnHasOrder);
		$arrSet = array_keys($arrValues);

		$return = '<input type="hidden" name="' . $this->strName . '" id="ctrl_' . $this->strId . '" value="' . implode(',', $arrSet) . '">' . ($blnHasOrder ? '
  <input type="hidden" name="' . $this->strOrderName . '" id="ctrl_' . $this->strOrderId . '" value="' . $this->{$this->orderField} . '">' : '') . '
  <div class="selector_container">' . ((($blnHasOrder || $this->isSortable) && \count($arrValues) > 1) ? '
    <p class="sort_hint">' . $GLOBALS['TL_LANG']['MSC']['dragItemsHint'] . '</p>' : '') . '
    <ul id="sort_' . $this->strId . '" class="' . (($blnHasOrder || $this->isSortable) ? 'sortable' : '') . '">';

		foreach ($arrValues as $k=>$v)
		{
			$return .= '<li data-id="' . $k . '">' . $v . '</li>';
		}

		$return .= '</ul>';

		if (!System::getContainer()->get('contao.picker.builder')->supportsContext($strContext))
		{
			$return .= '
	<p><button class="tl_submit" disabled>' . $GLOBALS['TL_LANG']['MSC']['changeSelection'] . '</button></p>';
		}
		else
		{
			$extras = $this->getPickerUrlExtras($arrValues);

			$return .= '
    <p><a href="' . StringUtil::ampersand(System::getContainer()->get('contao.picker.builder')->getUrl($strContext, $extras)) . '" class="tl_submit" id="picker_' . $this->strName . '">' . $GLOBALS['TL_LANG']['MSC']['changeSelection'] . '</a></p>
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
                $("ctrl_' . $this->strId . '").fireEvent("change");
              }
            }).post({"action":"reloadPicker", "name":"' . $this->strName . '", "value":value.join("\t"), "REQUEST_TOKEN":"' . REQUEST_TOKEN . '"});
          }
        });
      });
    </script>' . ($blnHasOrder || $this->isSortable ? '
    <script>Backend.makeMultiSrcSortable("sort_' . $this->strId . '", "ctrl_' . ($blnHasOrder ? $this->strOrderId : $this->strId) . '", "ctrl_' . $this->strId . '")</script>' : '');
		}

		$return = '<div>' . $return . '</div></div>';

		return $return;
	}

	protected function generateValues($blnHasOrder): array
	{
		$strRelatedTable = $this->getRelatedTable();

		if (!$strRelatedTable)
		{
			return (array) $this->varValue;
		}

		Controller::loadDataContainer($strRelatedTable);

		$arrValues = array();

		if (!empty($this->varValue))
		{
			$strIdList = implode(',', array_map('intval', (array) $this->varValue));
			$objRows = $this->Database->execute("SELECT * FROM $strRelatedTable WHERE id IN ($strIdList) ORDER BY FIND_IN_SET(id, '$strIdList')");

			if ($objRows->numRows)
			{
				$dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strRelatedTable]['config']['dataContainer'];
				$dc = new $dataContainer($strRelatedTable);

				while ($objRows->next())
				{
					$dc->id = $objRows->id;
					$dc->activeRecord = $objRows;

					$arrSet[] = $objRows->id;
					$arrValues[$objRows->id] = $this->renderLabel($objRows->row(), $dc);

					// showColumns
					if (\is_array($arrValues[$objRows->id]))
					{
						$arrValues[$objRows->id] = implode(', ', $arrValues[$objRows->id]);
					}
				}
			}

			// Apply a custom sort order
			if ($blnHasOrder)
			{
				$arrValues = ArrayUtil::sortByOrderField($arrValues, $this->{$this->orderField}, null, true);
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
		$label = vsprintf($labelConfig['format'], array_intersect_key($arrRow, array_flip($labelConfig['fields'])));

		if (\is_array($labelConfig['label_callback']))
		{
			$this->import($labelConfig['label_callback'][0]);

			return $this->{$labelConfig['label_callback'][0]}->{$labelConfig['label_callback'][1]}($arrRow, $label, $dc, $arrRow);
		}

		if (\is_callable($labelConfig['label_callback']))
		{
			return $labelConfig['label_callback']($arrRow, $label, $dc, $arrRow);
		}

		return $label ?: $arrRow['id'];
	}

	protected function getRelatedTable(): string
	{
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
