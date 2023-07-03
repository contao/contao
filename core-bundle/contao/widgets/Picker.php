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
		$arrValues = $this->generateValues();
		$arrSet = array_keys($arrValues);

		$return = '<input type="hidden" name="' . $this->strName . '" id="ctrl_' . $this->strId . '" value="' . implode(',', $arrSet) . '"' . ($this->onchange ? ' onchange="' . $this->onchange . '"' : '') . '>' . '
  <div class="selector_container">' . (($this->isSortable && \count($arrValues) > 1) ? '
    <p class="sort_hint">' . $GLOBALS['TL_LANG']['MSC']['dragItemsHint'] . '</p>' : '');

		if (($GLOBALS['TL_DCA'][$strRelatedTable]['list']['label']['showColumns'] ?? null) && !empty($arrValues))
		{
			System::loadLanguageFile($strRelatedTable);

			$showFields = $GLOBALS['TL_DCA'][$strRelatedTable]['list']['label']['fields'];

			$return .= '
<table class="tl_listing showColumns' . ($this->isSortable ? ' sortable' : '') . '">
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
    <ul id="sort_' . $this->strId . '" class="' . ($this->isSortable ? 'sortable' : '') . '">';

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
    <p><a href="' . StringUtil::ampersand(System::getContainer()->get('contao.picker.builder')->getUrl($strContext, $extras)) . '" class="tl_submit" id="picker_' . $this->strName . '">' . $GLOBALS['TL_LANG']['MSC']['changeSelection'] . '</a></p>
    <script>
      $("picker_' . $this->strName . '").addEvent("click", function(e) {
        e.preventDefault();
        Backend.openModalSelector({
          "id": "tl_listing",
          "title": ' . json_encode($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][0] ?? '') . ',
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
            }).post({"action":"reloadPicker", "name":"' . $this->strName . '", "value":value.join("\t"), "REQUEST_TOKEN":"' . System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue() . '"});
          }
        });
      });
    </script>' . ($this->isSortable ? '
    <script>Backend.makeMultiSrcSortable("sort_' . $this->strId . '", "ctrl_' . $this->strId . '", "ctrl_' . $this->strId . '")</script>' : '');
		}

		$return = '<div>' . $return . '</div></div>';

		return $return;
	}

	protected function generateValues(): array
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
			$strIdList = implode(',', array_map('intval', (array) $this->varValue));
			$objRows = $this->Database->execute("SELECT * FROM $strRelatedTable WHERE id IN ($strIdList) ORDER BY FIND_IN_SET(id, '$strIdList')");

			if ($objRows->numRows)
			{
				$dataContainer = DataContainer::getDriverForTable($strRelatedTable);

				$dc = (new \ReflectionClass($dataContainer))->newInstanceWithoutConstructor();
				$dc->table = $strRelatedTable;

				while ($objRows->next())
				{
					$dc->id = $objRows->id;
					$dc->activeRecord = $objRows;

					$arrValues[$objRows->id] = $this->renderLabel($objRows->row(), $dc);
				}
			}
		}

		return $arrValues;
	}

	protected function renderLabel(array $arrRow, DataContainer $dc)
	{
		$mode = $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] ?? DataContainer::MODE_SORTED;

		if ($mode === DataContainer::MODE_PARENT)
		{
			$callback = $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['child_record_callback'] ?? null;

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

		$label = $dc->generateRecordLabel($arrRow, $dc->table);

		return $label ?: $arrRow['id'] ?? '';
	}

	protected function getRelatedTable(): string
	{
		if (0 === strpos($this->context ?? '', 'dc.'))
		{
			return substr($this->context, 3);
		}

		$arrRelations = DcaExtractor::getInstance($this->strTable)->getRelations();

		return (string) ($arrRelations[$this->strField]['table'] ?? '');
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

		if (\is_array($this->rootNodes))
		{
			$extras['rootNodes'] = array_values($this->rootNodes);
		}

		return $extras;
	}
}
