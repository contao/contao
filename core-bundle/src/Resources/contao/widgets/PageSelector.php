<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;


/**
 * Provide methods to handle input field "page tree".
 *
 * @property array  $rootNodes
 * @property string $fieldType
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageSelector extends \Widget
{

	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Path nodes
	 * @var array
	 */
	protected $arrNodes = array();

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
		$this->import('Database');
		parent::__construct($arrAttributes);
	}


	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$this->import('BackendUser', 'User');

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = \System::getContainer()->get('session')->getBag('contao_backend');

		// Store the keyword
		if (\Input::post('FORM_SUBMIT') == 'item_selector')
		{
			$strKeyword = ltrim(\Input::postRaw('keyword'), '*');

			// Make sure the regular expression is valid
			if ($strKeyword != '')
			{
				try
				{
					$this->Database->prepare("SELECT * FROM tl_page WHERE title REGEXP ?")
								   ->limit(1)
								   ->execute($strKeyword);
				}
				catch (\Exception $e)
				{
					$strKeyword = '';
				}
			}

			$objSessionBag->set('page_selector_search', $strKeyword);
			$this->reload();
		}

		$tree = '';
		$this->getPathNodes();
		$for = $objSessionBag->get('page_selector_search');
		$arrFound = array();

		// Search for a specific page
		if ($for != '')
		{
			// Wrap in a try catch block in case the regular expression is invalid (see #7743)
			try
			{
				$strPattern = "CAST(title AS CHAR) REGEXP ?";

				if (substr(\Config::get('dbCollation'), -3) == '_ci')
				{
					$strPattern = "LOWER(CAST(title AS CHAR)) REGEXP LOWER(?)";
				}

				$objRoot = $this->Database->prepare("SELECT id FROM tl_page WHERE $strPattern GROUP BY id")
										  ->execute($for);

				if ($objRoot->numRows < 1)
				{
					$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = array(0);
				}
				else
				{
					$arrIds = array();

					// Respect existing limitations
					if (is_array($this->rootNodes))
					{
						while ($objRoot->next())
						{
							// Predefined node set (see #3563)
							if (count(array_intersect($this->rootNodes, $this->Database->getParentRecords($objRoot->id, 'tl_page'))) > 0)
							{
								$arrFound[] = $objRoot->id;
								$arrIds[] = $objRoot->id;
							}
						}
					}
					elseif ($this->User->isAdmin)
					{
						// Show all pages to admins
						while ($objRoot->next())
						{
							$arrFound[] = $objRoot->id;
							$arrIds[] = $objRoot->id;
						}
					}
					else
					{
						while ($objRoot->next())
						{
							// Show only mounted pages to regular users
							if (count(array_intersect($this->User->pagemounts, $this->Database->getParentRecords($objRoot->id, 'tl_page'))) > 0)
							{
								$arrFound[] = $objRoot->id;
								$arrIds[] = $objRoot->id;
							}
						}
					}

					$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'] = array_unique($arrIds);
				}
			}
			catch (\Exception $e) {}
		}

		$strNode = $objSessionBag->get('tl_page_picker');

		// Unset the node if it is not within the predefined node set (see #5899)
		if ($strNode > 0 && is_array($this->rootNodes))
		{
			if (!in_array($strNode, $this->Database->getChildRecords($this->rootNodes, 'tl_page')))
			{
				$objSessionBag->remove('tl_page_picker');
			}
		}

		// Add the breadcrumb menu
		if (\Input::get('do') != 'page')
		{
			\Backend::addPagesBreadcrumb('tl_page_picker');
		}

		// Root nodes (breadcrumb menu)
		if (!empty($GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root']))
		{
			$root = $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['root'];

			// Allow only those roots that are allowed in root nodes
			if (is_array($this->rootNodes))
			{
				$root = array_intersect(array_merge($this->rootNodes, $this->Database->getChildRecords($this->rootNodes, 'tl_page')), $root);

				if (empty($root))
				{
					$root = $this->rootNodes;

					// Hide the breadcrumb
					$GLOBALS['TL_DCA']['tl_page']['list']['sorting']['breadcrumb'] = '';
				}
			}

			$nodes = $this->eliminateNestedPages($root);

			foreach ($nodes as $node)
			{
				$tree .= $this->renderPagetree($node, -20, false, false, $arrFound);
			}
		}

		// Predefined node set (see #3563)
		elseif (is_array($this->rootNodes))
		{
			$nodes = $this->eliminateNestedPages($this->rootNodes);

			foreach ($nodes as $node)
			{
				$tree .= $this->renderPagetree($node, -20, false, false, $arrFound);
			}
		}

		// Show all pages to admins
		elseif ($this->User->isAdmin)
		{
			$objPage = $this->Database->prepare("SELECT id FROM tl_page WHERE pid=? ORDER BY sorting")
									  ->execute(0);

			while ($objPage->next())
			{
				$tree .= $this->renderPagetree($objPage->id, -20, false, false, $arrFound);
			}
		}

		// Show only mounted pages to regular users
		else
		{
			$nodes = $this->eliminateNestedPages($this->User->pagemounts);

			foreach ($nodes as $node)
			{
				$tree .= $this->renderPagetree($node, -20, false, false, $arrFound);
			}
		}

		// Select all checkboxes
		if ($this->fieldType == 'checkbox')
		{
			$strReset = "\n" . '    <li class="tl_folder"><div class="tl_left">&nbsp;</div> <div class="tl_right"><label for="check_all_' . $this->strId . '" class="tl_change_selected">' . $GLOBALS['TL_LANG']['MSC']['selectAll'] . '</label> <input type="checkbox" id="check_all_' . $this->strId . '" class="tl_tree_checkbox" value="" onclick="Backend.toggleCheckboxGroup(this,\'' . $this->strName . '\')"></div><div style="clear:both"></div></li>';
		}
		// Reset radio button selection
		else
		{
			$strReset = "\n" . '    <li class="tl_folder"><div class="tl_left">&nbsp;</div> <div class="tl_right"><label for="reset_' . $this->strId . '" class="tl_change_selected">' . $GLOBALS['TL_LANG']['MSC']['resetSelected'] . '</label> <input type="radio" name="' . $this->strName . '" id="reset_' . $this->strName . '" class="tl_tree_radio" value="" onfocus="Backend.getScrollOffset()"></div><div style="clear:both"></div></li>';
		}

		// Return the tree
		return '<ul class="tl_listing tree_view picker_selector'.(($this->strClass != '') ? ' ' . $this->strClass : '').'" id="'.$this->strId.'" data-callback="reloadPagetree" data-inserttag="link_url">
    <li class="tl_folder_top"><div class="tl_left">'.\Image::getHtml($GLOBALS['TL_DCA']['tl_page']['list']['sorting']['icon'] ?: 'pagemounts.svg').' '.(\Config::get('websiteTitle') ?: 'Contao Open Source CMS').'</div> <div class="tl_right">&nbsp;</div><div style="clear:both"></div></li><li class="parent" id="'.$this->strId.'_parent"><ul>'.$tree.$strReset.'
  </ul></li></ul>';
	}


	/**
	 * Generate a particular subpart of the page tree and return it as HTML string
	 *
	 * @param integer $id
	 * @param string  $strField
	 * @param integer $level
	 *
	 * @return string
	 */
	public function generateAjax($id, $strField, $level)
	{
		if (!\Environment::get('isAjaxRequest'))
		{
			return '';
		}

		$this->strField = $strField;
		$this->loadDataContainer($this->strTable);

		// Load current values
		switch ($GLOBALS['TL_DCA'][$this->strTable]['config']['dataContainer'])
		{
			case 'File':
				if (\Config::get($this->strField) != '')
				{
					$this->varValue = \Config::get($this->strField);
				}
				break;

			case 'Table':
				if (!$this->Database->fieldExists($this->strField, $this->strTable))
				{
					break;
				}

				$objField = $this->Database->prepare("SELECT " . $this->strField . " FROM " . $this->strTable . " WHERE id=?")
										   ->limit(1)
										   ->execute($this->strId);

				if ($objField->numRows)
				{
					$this->varValue = \StringUtil::deserialize($objField->{$this->strField});
				}
				break;
		}

		$this->getPathNodes();

		// Load the requested nodes
		$tree = '';
		$level = $level * 20;

		$objPage = $this->Database->prepare("SELECT id FROM tl_page WHERE pid=? ORDER BY sorting")
								  ->execute($id);

		while ($objPage->next())
		{
			$tree .= $this->renderPagetree($objPage->id, $level);
		}

		return $tree;
	}


	/**
	 * Recursively render the pagetree
	 *
	 * @param integer $id
	 * @param integer $intMargin
	 * @param boolean $protectedPage
	 * @param boolean $blnNoRecursion
	 * @param array   $arrFound
	 *
	 * @return string
	 */
	protected function renderPagetree($id, $intMargin, $protectedPage=false, $blnNoRecursion=false, $arrFound=array())
	{
		static $session;

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = \System::getContainer()->get('session')->getBag('contao_backend');

		$session = $objSessionBag->all();

		$flag = substr($this->strField, 0, 2);
		$node = 'tree_' . $this->strTable . '_' . $this->strField;
		$xtnode = 'tree_' . $this->strTable . '_' . $this->strName;

		// Get the session data and toggle the nodes
		if (\Input::get($flag.'tg'))
		{
			$session[$node][\Input::get($flag.'tg')] = (isset($session[$node][\Input::get($flag.'tg')]) && $session[$node][\Input::get($flag.'tg')] == 1) ? 0 : 1;
			$objSessionBag->replace($session);
			$this->redirect(preg_replace('/(&(amp;)?|\?)'.$flag.'tg=[^& ]*/i', '', \Environment::get('request')));
		}

		$objPage = $this->Database->prepare("SELECT id, alias, type, protected, published, start, stop, hide, title FROM tl_page WHERE id=?")
								  ->limit(1)
								  ->execute($id);

		// Return if there is no result
		if ($objPage->numRows < 1)
		{
			return '';
		}

		$return = '';
		$intSpacing = 20;
		$childs = array();

		// Check whether there are child records
		if (!$blnNoRecursion)
		{
			$objNodes = $this->Database->prepare("SELECT id FROM tl_page WHERE pid=?" . (!empty($arrFound) ? " AND id IN(" . implode(',', array_map('intval', $arrFound)) . ")" : '') . " ORDER BY sorting")
									   ->execute($id);

			if ($objNodes->numRows)
			{
				$childs = $objNodes->fetchEach('id');
			}
		}

		$return .= "\n    " . '<li class="'.(($objPage->type == 'root') ? 'tl_folder' : 'tl_file').' toggle_select hover-div"><div class="tl_left" style="padding-left:'.($intMargin + $intSpacing).'px">';

		$folderAttribute = 'style="margin-left:20px"';
		$session[$node][$id] = is_numeric($session[$node][$id]) ? $session[$node][$id] : 0;
		$level = ($intMargin / $intSpacing + 1);
		$blnIsOpen = (!empty($arrFound) || $session[$node][$id] == 1 || in_array($id, $this->arrNodes));

		if (!empty($childs))
		{
			$folderAttribute = '';
			$img = $blnIsOpen ? 'folMinus.svg' : 'folPlus.svg';
			$alt = $blnIsOpen ? $GLOBALS['TL_LANG']['MSC']['collapseNode'] : $GLOBALS['TL_LANG']['MSC']['expandNode'];
			$return .= '<a href="'.\Backend::addToUrl($flag.'tg='.$id).'" title="'.\StringUtil::specialchars($alt).'" onclick="return AjaxRequest.togglePagetree(this,\''.$xtnode.'_'.$id.'\',\''.$this->strField.'\',\''.$this->strName.'\','.$level.')">'.\Image::getHtml($img, '', 'style="margin-right:2px"').'</a>';
		}

		// Set the protection status
		$objPage->protected = ($objPage->protected || $protectedPage);

		// Add the current page
		if (!empty($childs))
		{
			$return .= \Image::getHtml($this->getPageStatusIcon($objPage), '', $folderAttribute).' <a href="' . \Backend::addToUrl('pn='.$objPage->id) . '" title="'.\StringUtil::specialchars($objPage->title . ' (' . $objPage->alias . \Config::get('urlSuffix') . ')').'">'.(($objPage->type == 'root') ? '<strong>' : '').$objPage->title.(($objPage->type == 'root') ? '</strong>' : '').'</a></div> <div class="tl_right">';
		}
		else
		{
			$return .= \Image::getHtml($this->getPageStatusIcon($objPage), '', $folderAttribute).' '.(($objPage->type == 'root') ? '<strong>' : '').$objPage->title.(($objPage->type == 'root') ? '</strong>' : '').'</div> <div class="tl_right">';
		}

		// Add checkbox or radio button
		switch ($this->fieldType)
		{
			case 'checkbox':
				$return .= '<input type="checkbox" name="'.$this->strName.'[]" id="'.$this->strName.'_'.$id.'" class="tl_tree_checkbox" value="'.\StringUtil::specialchars($id).'" onfocus="Backend.getScrollOffset()"'.static::optionChecked($id, $this->varValue).'>';
				break;

			default:
			case 'radio':
				$return .= '<input type="radio" name="'.$this->strName.'" id="'.$this->strName.'_'.$id.'" class="tl_tree_radio" value="'.\StringUtil::specialchars($id).'" onfocus="Backend.getScrollOffset()"'.static::optionChecked($id, $this->varValue).'>';
				break;
		}

		$return .= '</div><div style="clear:both"></div></li>';

		// Begin a new submenu
		if ($blnIsOpen || !empty($childs) && $objSessionBag->get('page_selector_search') != '')
		{
			$return .= '<li class="parent" id="'.$node.'_'.$id.'"><ul class="level_'.$level.'">';

			for ($k=0, $c=count($childs); $k<$c; $k++)
			{
				$return .= $this->renderPagetree($childs[$k], ($intMargin + $intSpacing), $objPage->protected, $blnNoRecursion, $arrFound);
			}

			$return .= '</ul></li>';
		}

		return $return;
	}


	/**
	 * Get the IDs of all parent pages of the selected pages, so they are expanded automatically
	 */
	protected function getPathNodes()
	{
		if (!$this->varValue)
		{
			return;
		}

		if (!is_array($this->varValue))
		{
			$this->varValue = array($this->varValue);
		}

		foreach ($this->varValue as $id)
		{
			$arrPids = $this->Database->getParentRecords($id, 'tl_page');
			array_shift($arrPids); // the first element is the ID of the page itself
			$this->arrNodes = array_merge($this->arrNodes, $arrPids);
		}
	}
}
