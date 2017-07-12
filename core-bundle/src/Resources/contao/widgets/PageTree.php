<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\DataContainer\DcaFilterInterface;


/**
 * Provide methods to handle input field "page tree".
 *
 * @property string  $orderField
 * @property boolean $multiple
 * @property array   $rootNodes
 * @property string  $fieldType
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageTree extends \Widget implements DcaFilterInterface
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
		$this->import('Database');
		parent::__construct($arrAttributes);

		// Prepare the order field
		if ($this->orderField != '')
		{
			$this->strOrderId = $this->orderField . str_replace($this->strField, '', $this->strId);
			$this->strOrderName = $this->orderField . str_replace($this->strField, '', $this->strName);

			// Retrieve the order value
			$objRow = $this->Database->prepare("SELECT {$this->orderField} FROM {$this->strTable} WHERE id=?")
						   ->limit(1)
						   ->execute($this->activeRecord->id);

			$tmp = \StringUtil::deserialize($objRow->{$this->orderField});
			$this->{$this->orderField} = (!empty($tmp) && is_array($tmp)) ? array_filter($tmp) : array();
		}
	}


	/**
	 * {@inheritdoc}
	 */
	public function getDcaFilter()
	{
		$arrFilters = array();

		// Predefined node set (see #3563)
		if (is_array($this->rootNodes))
		{
			// Allow only those roots that are allowed in root nodes
			if (!empty($GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root']))
			{
				$root = array_intersect(array_merge($this->rootNodes, $this->Database->getChildRecords($this->rootNodes, 'tl_page')), $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root']);

				if (empty($root))
				{
					$root = $this->rootNodes;
					$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] = ''; // hide the breadcrumb menu
				}

				$arrFilters['root'] = $this->eliminateNestedPages($root);
			}
			else
			{
				$arrFilters['root'] = $this->eliminateNestedPages($this->rootNodes);
			}
		}

		if ($this->fieldType)
		{
			$arrFilters['fieldType'] = $this->fieldType;
		}

		return $arrFilters;
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
		if ($this->orderField != '')
		{
			$arrNew = explode(',', \Input::post($this->strOrderName));

			// Only proceed if the value has changed
			if ($arrNew !== $this->{$this->orderField})
			{
				$this->Database->prepare("UPDATE {$this->strTable} SET tstamp=?, {$this->orderField}=? WHERE id=?")
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
		elseif (strpos($varInput, ',') === false)
		{
			return $this->multiple ? array(intval($varInput)) : intval($varInput);
		}
		else
		{
			$arrValue = array_map('intval', array_filter(explode(',', $varInput)));

			return $this->multiple ? $arrValue : $arrValue[0];
		}
	}


	/**
	 * Check the selected value
	 *
	 * @param mixed $varInput
	 */
	protected function checkValue($varInput)
	{
		if ($varInput == '' || !is_array($this->rootNodes))
		{
			return;
		}

		if (strpos($varInput, ',') === false)
		{
			$arrIds = array(intval($varInput));
		}
		else
		{
			$arrIds = array_map('intval', array_filter(explode(',', $varInput)));
		}

		if (count(array_diff($arrIds, array_merge($this->rootNodes, $this->Database->getChildRecords($this->rootNodes, 'tl_page')))) > 0)
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
		$blnHasOrder = ($this->orderField != '' && is_array($this->{$this->orderField}));

		if (!empty($this->varValue)) // Can be an array
		{
			$objPages = \PageModel::findMultipleByIds((array)$this->varValue);

			if ($objPages !== null)
			{
				while ($objPages->next())
				{
					$arrSet[] = $objPages->id;
					$arrValues[$objPages->id] = \Image::getHtml($this->getPageStatusIcon($objPages)) . ' ' . $objPages->title . ' (' . $objPages->alias . \Config::get('urlSuffix') . ')';
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

		$return = '<input type="hidden" name="'.$this->strName.'" id="ctrl_'.$this->strId.'" value="'.implode(',', $arrSet).'">' . ($blnHasOrder ? '
  <input type="hidden" name="'.$this->strOrderName.'" id="ctrl_'.$this->strOrderId.'" value="'.$this->{$this->orderField}.'">' : '') . '
  <div class="selector_container">' . (($blnHasOrder && count($arrValues) > 1) ? '
    <p class="sort_hint">' . $GLOBALS['TL_LANG']['MSC']['dragItemsHint'] . '</p>' : '') . '
    <ul id="sort_'.$this->strId.'" class="'.($blnHasOrder ? 'sortable' : '').'">';

		foreach ($arrValues as $k=>$v)
		{
			$return .= '<li data-id="'.$k.'">'.$v.'</li>';
		}

		$return .= '</ul>
    <p><a href="' . ampersand(\System::getContainer()->get('router')->generate('contao_backend_picker', array('do'=>'page', 'context'=>'page', 'target'=>$this->strTable.'.'.$this->strField.'.'.$this->activeRecord->id, 'value'=>implode(',', $arrSet), 'popup'=>1))) . '" class="tl_submit" id="pt_' . $this->strName . '">'.$GLOBALS['TL_LANG']['MSC']['changeSelection'].'</a></p>
    <script>
      $("pt_' . $this->strName . '").addEvent("click", function(e) {
        e.preventDefault();
        Backend.openModalSelector({
          "title": "' . \StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][0])) . '",
          "url": this.href,
          "callback": function(table, value) {
            new Request.Contao({
              evalScripts: false,
              onSuccess: function(txt, json) {
                $("ctrl_' . $this->strId . '").getParent("div").set("html", json.content);
                json.javascript && Browser.exec(json.javascript);
              }
            }).post({"action":"reloadPagetree", "name":"' . $this->strId . '", "value":value.join("\t"), "REQUEST_TOKEN":"' . REQUEST_TOKEN . '"});
          }
        });
      });
    </script>' . ($blnHasOrder ? '
    <script>Backend.makeMultiSrcSortable("sort_'.$this->strId.'", "ctrl_'.$this->strOrderId.'", "ctrl_'.$this->strId.'")</script>' : '') . '
  </div>';

		$return = '<div>' . $return . '</div>';

		return $return;
	}
}
