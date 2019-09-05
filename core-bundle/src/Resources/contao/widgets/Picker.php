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
		$blnHasOrder = ($this->orderField != '' && \is_array($this->{$this->orderField}));
		$arrValues = $this->generateValues($blnHasOrder);
		$arrSet = array_keys($arrValues);

		$return = '<input type="hidden" name="'.$this->strName.'" id="ctrl_'.$this->strId.'" value="'.implode(',', $arrSet).'">' . ($blnHasOrder ? '
  <input type="hidden" name="'.$this->strOrderName.'" id="ctrl_'.$this->strOrderId.'" value="'.$this->{$this->orderField}.'">' : '') . '
  <div class="selector_container">' . (($blnHasOrder && \count($arrValues) > 1) ? '
    <p class="sort_hint">' . $GLOBALS['TL_LANG']['MSC']['dragItemsHint'] . '</p>' : '') . '
    <ul id="sort_'.$this->strId.'" class="'.($blnHasOrder ? 'sortable' : '').'">';

		foreach ($arrValues as $k=>$v)
		{
			$return .= '<li data-id="'.$k.'">'.$v.'</li>';
		}

		$return .= '</ul>';

		if (!System::getContainer()->get('contao.picker.builder')->supportsContext($this->context))
		{
			$return .= '
	<p><button class="tl_submit" disabled>'.$GLOBALS['TL_LANG']['MSC']['changeSelection'].'</button></p>';
		}
		else
		{
			$extras = array
			(
				'fieldType' => $this->fieldType,
				'source' => $this->strTable.'.'.$this->currentRecord,
			);

			$return .= '
    <p><a href="' . ampersand(System::getContainer()->get('contao.picker.builder')->getUrl($this->context, $extras)) . '" class="tl_submit" id="picker_' . $this->strName . '">'.$GLOBALS['TL_LANG']['MSC']['changeSelection'].'</a></p>
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
    </script>' . ($blnHasOrder ? '
    <script>Backend.makeMultiSrcSortable("sort_'.$this->strId.'", "ctrl_'.$this->strOrderId.'", "ctrl_'.$this->strId.'")</script>' : '');
		}

		$return = '<div>' . $return . '</div></div>';

		return $return;
	}

	protected function generateValues($blnHasOrder): array
	{
		$arrRelations = DcaExtractor::getInstance($this->strTable)->getRelations();
		$strRelatedTable = $arrRelations[$this->strField]['table'] ?? null;

		if (!$strRelatedTable)
		{
			return (array) $this->varValue;
		}

		\Controller::loadDataContainer($strRelatedTable);

		$arrValues = array();

		if (!empty($this->varValue)) // can be an array
		{
			$objRows = $this->Database->execute("SELECT * FROM $strRelatedTable WHERE id IN (".implode(',', array_map('intval', (array) $this->varValue)).")");

			if ($objRows->numRows)
			{
				while ($objRows->next())
				{
					$arrSet[] = $objRows->id;
					$arrValues[$objRows->id] = $this->renderLabel($objRows->row(), $strRelatedTable);
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

	protected function renderLabel(array $arrRow, string $strRelatedTable)
	{
		$mode = $GLOBALS['TL_DCA'][$strRelatedTable]['list']['sorting']['mode'] ?? 1;

		if ($mode === 4)
		{
			$callback = $GLOBALS['TL_DCA'][$strRelatedTable]['list']['sorting']['child_record_callback'];

			if (is_array($callback))
			{
				$this->import($callback[0]);

				return $this->{$callback[0]}->{$callback[1]}($arrRow);
			}

			if (\is_callable($callback))
			{
				return $callback($arrRow);
			}
		}

		$labelConfig = &$GLOBALS['TL_DCA'][$strRelatedTable]['list']['label'];
		$label = vsprintf($labelConfig['format'], array_intersect_key($arrRow, array_flip($labelConfig['fields'])));

		if (is_array($labelConfig['label_callback']))
		{
			$this->import($labelConfig['label_callback'][0]);

			return $this->{$labelConfig['label_callback'][0]}->{$labelConfig['label_callback'][1]}($arrRow, $label);
		}

		if (\is_callable($labelConfig['label_callback']))
		{
			return $labelConfig['label_callback']($arrRow, $label);
		}

		return $arrRow['id'];
	}
}
