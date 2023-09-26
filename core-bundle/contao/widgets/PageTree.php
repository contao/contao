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
 * Provide methods to handle input field "page tree".
 *
 * @property string  $orderField
 * @property boolean $multiple
 * @property array   $rootNodes
 * @property string  $fieldType
 */
class PageTree extends Widget
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
	 * Order ID
	 * @var string
	 */
	protected $strOrderId;

	/**
	 * Order name
	 * @var string
	 */
	protected $strOrderName;

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
			trigger_deprecation('contao/core-bundle', '4.10', 'Using "orderField" for the page tree has been deprecated and will no longer work in Contao 5.0. Use "isSortable" instead.');

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
		$this->checkValue($varInput);

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

		$arrValue = array_map('\intval', array_filter(explode(',', $varInput)));

		return $this->multiple ? $arrValue : $arrValue[0];
	}

	/**
	 * Check the selected value
	 *
	 * @param mixed $varInput
	 */
	protected function checkValue($varInput)
	{
		if (!$varInput || !\is_array($this->rootNodes))
		{
			return;
		}

		if (strpos($varInput, ',') === false)
		{
			$arrIds = array((int) $varInput);
		}
		else
		{
			$arrIds = array_map('\intval', array_filter(explode(',', $varInput)));
		}

		if (\count(array_diff($arrIds, array_merge($this->rootNodes, $this->Database->getChildRecords($this->rootNodes, 'tl_page')))) > 0)
		{
			$this->addError($GLOBALS['TL_LANG']['ERR']['invalidPages']);
		}
	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$arrSet = array();
		$arrValues = array();
		$blnHasOrder = $this->orderField && \is_array($this->{$this->orderField});

		// $this->varValue can be an array, so use empty() here
		if (!empty($this->varValue))
		{
			$objPages = PageModel::findMultipleByIds((array) $this->varValue);

			if ($objPages !== null)
			{
				foreach ($objPages as $objPage)
				{
					$objPage->loadDetails();

					$arrSet[] = $objPage->id;
					$arrValues[$objPage->id] = Image::getHtml($this->getPageStatusIcon($objPage)) . ' ' . $objPage->title . ' (' . ($objPage->urlPrefix ? ($objPage->urlPrefix . '/') : '') . $objPage->alias . $objPage->urlSuffix . ')';
				}
			}

			// Apply a custom sort order
			if ($blnHasOrder)
			{
				$arrValues = ArrayUtil::sortByOrderField($arrValues, $this->{$this->orderField}, null, true);
			}
		}

		$return = '<input type="hidden" name="' . $this->strName . '" id="ctrl_' . $this->strId . '" value="' . implode(',', $arrSet) . '">' . ($blnHasOrder ? '
  <input type="hidden" name="' . $this->strOrderName . '" id="ctrl_' . $this->strOrderId . '" value="' . implode(',', $this->{$this->orderField}) . '"' . ($this->onchange ? ' onchange="' . $this->onchange . '"' : '') . '>' : '') . '
  <div class="selector_container">' . ((($blnHasOrder || $this->isSortable) && \count($arrValues) > 1) ? '
    <p class="sort_hint">' . $GLOBALS['TL_LANG']['MSC']['dragItemsHint'] . '</p>' : '') . '
    <ul id="sort_' . $this->strId . '" class="' . ($blnHasOrder || $this->isSortable ? 'sortable' : '') . '">';

		foreach ($arrValues as $k=>$v)
		{
			$return .= '<li data-id="' . $k . '">' . $v . '</li>';
		}

		$return .= '</ul>';

		if (!System::getContainer()->get('contao.picker.builder')->supportsContext('page'))
		{
			$return .= '
	<p><button class="tl_submit" disabled>' . $GLOBALS['TL_LANG']['MSC']['changeSelection'] . '</button></p>';
		}
		else
		{
			$extras = $this->getPickerUrlExtras($arrValues);

			$return .= '
    <p><a href="' . StringUtil::ampersand(System::getContainer()->get('contao.picker.builder')->getUrl('page', $extras)) . '" class="tl_submit" id="pt_' . $this->strName . '">' . $GLOBALS['TL_LANG']['MSC']['changeSelection'] . '</a></p>
    <script>
      $("pt_' . $this->strName . '").addEvent("click", function(e) {
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
            }).post({"action":"reloadPagetree", "name":"' . $this->strName . '", "value":value.join("\t"), "REQUEST_TOKEN":"' . REQUEST_TOKEN . '"});
          }
        });
      });
    </script>' . ($blnHasOrder || $this->isSortable ? '
    <script>Backend.makeMultiSrcSortable("sort_' . $this->strId . '", "ctrl_' . ($blnHasOrder ? $this->strOrderId : $this->strId) . '", "ctrl_' . $this->strId . '")</script>' : '');
		}

		$return = '<div>' . $return . '</div></div>';

		return $return;
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
		$extras['fieldType'] = $this->fieldType;
		$extras['source'] = $this->strTable . '.' . $this->currentRecord;

		if (\is_array($this->rootNodes))
		{
			$extras['rootNodes'] = array_values($this->rootNodes);
		}

		return $extras;
	}
}

class_alias(PageTree::class, 'PageTree');
