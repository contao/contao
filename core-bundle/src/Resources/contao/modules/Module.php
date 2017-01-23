<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Parent class for front end modules.
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $tstamp
 * @property string  $name
 * @property string  $headline
 * @property string  $type
 * @property integer $levelOffset
 * @property integer $showLevel
 * @property boolean $hardLimit
 * @property boolean $showProtected
 * @property boolean $defineRoot
 * @property integer $rootPage
 * @property string  $navigationTpl
 * @property string  $customTpl
 * @property string  $pages
 * @property string  $orderPages
 * @property boolean $showHidden
 * @property string  $customLabel
 * @property boolean $autologin
 * @property integer $jumpTo
 * @property boolean $redirectBack
 * @property string  $cols
 * @property array   $editable
 * @property string  $memberTpl
 * @property integer $form
 * @property string  $queryType
 * @property boolean $fuzzy
 * @property integer $contextLength
 * @property integer $totalLength
 * @property integer $perPage
 * @property string  $searchType
 * @property string  $searchTpl
 * @property string  $inColumn
 * @property integer $skipFirst
 * @property boolean $loadFirst
 * @property string  $size
 * @property boolean $transparent
 * @property string  $flashvars
 * @property string  $altContent
 * @property string  $source
 * @property string  $singleSRC
 * @property string  $url
 * @property boolean $interactive
 * @property string  $flashID
 * @property string  $flashJS
 * @property string  $imgSize
 * @property boolean $useCaption
 * @property boolean $fullsize
 * @property string  $multiSRC
 * @property string  $orderSRC
 * @property string  $html
 * @property integer $rss_cache
 * @property string  $rss_feed
 * @property string  $rss_template
 * @property integer $numberOfItems
 * @property boolean $disableCaptcha
 * @property string  $reg_groups
 * @property boolean $reg_allowLogin
 * @property boolean $reg_skipName
 * @property string  $reg_close
 * @property boolean $reg_assignDir
 * @property string  $reg_homeDir
 * @property boolean $reg_activate
 * @property integer $reg_jumpTo
 * @property string  $reg_text
 * @property string  $reg_password
 * @property boolean $protected
 * @property string  $groups
 * @property boolean $guests
 * @property string  $cssID
 * @property string  $hl
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
abstract class Module extends \Frontend
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate;

	/**
	 * Column
	 * @var string
	 */
	protected $strColumn;

	/**
	 * Model
	 * @var ModuleModel
	 */
	protected $objModel;

	/**
	 * Current record
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Style array
	 * @var array
	 */
	protected $arrStyle = array();


	/**
	 * Initialize the object
	 *
	 * @param ModuleModel $objModule
	 * @param string      $strColumn
	 */
	public function __construct($objModule, $strColumn='main')
	{
		if ($objModule instanceof Model || $objModule instanceof Model\Collection)
		{
			/** @var ModuleModel $objModel */
			$objModel = $objModule;

			if ($objModel instanceof Model\Collection)
			{
				$objModel = $objModel->current();
			}

			$this->objModel = $objModel;
		}

		parent::__construct();

		$this->arrData = $objModule->row();
		$this->cssID = \StringUtil::deserialize($objModule->cssID, true);

		if ($this->customTpl != '' && TL_MODE == 'FE')
		{
			$this->strTemplate = $this->customTpl;
		}

		$arrHeadline = \StringUtil::deserialize($objModule->headline);
		$this->headline = is_array($arrHeadline) ? $arrHeadline['value'] : $arrHeadline;
		$this->hl = is_array($arrHeadline) ? $arrHeadline['unit'] : 'h1';
		$this->strColumn = $strColumn;
	}


	/**
	 * Set an object property
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue)
	{
		$this->arrData[$strKey] = $varValue;
	}


	/**
	 * Return an object property
	 *
	 * @param string $strKey
	 *
	 * @return mixed
	 */
	public function __get($strKey)
	{
		if (isset($this->arrData[$strKey]))
		{
			return $this->arrData[$strKey];
		}

		return parent::__get($strKey);
	}


	/**
	 * Check whether a property is set
	 *
	 * @param string $strKey
	 *
	 * @return boolean
	 */
	public function __isset($strKey)
	{
		return isset($this->arrData[$strKey]);
	}


	/**
	 * Return the model
	 *
	 * @return Model
	 */
	public function getModel()
	{
		return $this->objModel;
	}


	/**
	 * Parse the template
	 *
	 * @return string
	 */
	public function generate()
	{
		$this->Template = new \FrontendTemplate($this->strTemplate);
		$this->Template->setData($this->arrData);

		$this->compile();

		// Do not change this order (see #6191)
		$this->Template->style = !empty($this->arrStyle) ? implode(' ', $this->arrStyle) : '';
		$this->Template->class = trim('mod_' . $this->type . ' ' . $this->cssID[1]);
		$this->Template->cssID = !empty($this->cssID[0]) ? ' id="' . $this->cssID[0] . '"' : '';

		$this->Template->inColumn = $this->strColumn;

		if ($this->Template->headline == '')
		{
			$this->Template->headline = $this->headline;
		}

		if ($this->Template->hl == '')
		{
			$this->Template->hl = $this->hl;
		}

		if (!empty($this->objModel->classes) && is_array($this->objModel->classes))
		{
			$this->Template->class .= ' ' . implode(' ', $this->objModel->classes);
		}

		return $this->Template->parse();
	}


	/**
	 * Compile the current element
	 */
	abstract protected function compile();


	/**
	 * Recursively compile the navigation menu and return it as HTML string
	 *
	 * @param integer $pid
	 * @param integer $level
	 * @param string  $host
	 * @param string  $language
	 *
	 * @return string
	 */
	protected function renderNavigation($pid, $level=1, $host=null, $language=null)
	{
		// Get all active subpages
		$objSubpages = \PageModel::findPublishedSubpagesWithoutGuestsByPid($pid, $this->showHidden, $this instanceof ModuleSitemap);

		if ($objSubpages === null)
		{
			return '';
		}

		$items = array();
		$groups = array();

		// Get all groups of the current front end user
		if (FE_USER_LOGGED_IN)
		{
			$this->import('FrontendUser', 'User');
			$groups = $this->User->groups;
		}

		// Layout template fallback
		if ($this->navigationTpl == '')
		{
			$this->navigationTpl = 'nav_default';
		}

		/** @var FrontendTemplate|object $objTemplate */
		$objTemplate = new \FrontendTemplate($this->navigationTpl);

		$objTemplate->pid = $pid;
		$objTemplate->type = get_class($this);
		$objTemplate->cssID = $this->cssID; // see #4897
		$objTemplate->level = 'level_' . $level++;

		/** @var PageModel $objPage */
		global $objPage;

		// Browse subpages
		foreach ($objSubpages as $objSubpage)
		{
			// Skip hidden sitemap pages
			if ($this instanceof ModuleSitemap && $objSubpages->sitemap == 'map_never')
			{
				continue;
			}

			$subitems = '';
			$_groups = \StringUtil::deserialize($objSubpage->groups);

			// Override the domain (see #3765)
			if ($host !== null)
			{
				$objSubpage->domain = $host;
			}

			// Do not show protected pages unless a front end user is logged in
			if (!$objSubpage->protected || (is_array($_groups) && count(array_intersect($_groups, $groups))) || $this->showProtected || ($this instanceof ModuleSitemap && $objSubpage->sitemap == 'map_always'))
			{
				// Check whether there will be subpages
				if ($objSubpage->subpages > 0 && (!$this->showLevel || $this->showLevel >= $level || (!$this->hardLimit && ($objPage->id == $objSubpage->id || in_array($objPage->id, $this->Database->getChildRecords($objSubpage->id, 'tl_page'))))))
				{
					$subitems = $this->renderNavigation($objSubpage->id, $level, $host, $language);
				}

				$href = null;

				// Get href
				switch ($objSubpage->type)
				{
					case 'redirect':
						$href = $objSubpage->url;

						if (strncasecmp($href, 'mailto:', 7) === 0)
						{
							$href = \StringUtil::encodeEmail($href);
						}
						break;

					case 'forward':
						if ($objSubpage->jumpTo)
						{
							/** @var PageModel $objNext */
							$objNext = $objSubpage->getRelated('jumpTo');
						}
						else
						{
							$objNext = \PageModel::findFirstPublishedRegularByPid($objSubpage->id);
						}

						// Hide the link if the target page is invisible
						if (!($objNext instanceof PageModel) || !$objNext->published || ($objNext->start != '' && $objNext->start > time()) || ($objNext->stop != '' && $objNext->stop < time()))
						{
							continue(2);
						}

						$href = $objNext->getFrontendUrl();
						break;

					default:
						$href = $objSubpage->getFrontendUrl();
						break;
				}

				$row = $objSubpage->row();
				$trail = in_array($objSubpage->id, $objPage->trail);

				// Active page
				if (($objPage->id == $objSubpage->id || $objSubpage->type == 'forward' && $objPage->id == $objSubpage->jumpTo) && !($this instanceof ModuleSitemap) && $href == \Environment::get('request'))
				{
					// Mark active forward pages (see #4822)
					$strClass = (($objSubpage->type == 'forward' && $objPage->id == $objSubpage->jumpTo) ? 'forward' . ($trail ? ' trail' : '') : 'active') . (($subitems != '') ? ' submenu' : '') . ($objSubpage->protected ? ' protected' : '') . (($objSubpage->cssClass != '') ? ' ' . $objSubpage->cssClass : '');

					$row['isActive'] = true;
					$row['isTrail'] = false;
				}

				// Regular page
				else
				{
					$strClass = (($subitems != '') ? 'submenu' : '') . ($objSubpage->protected ? ' protected' : '') . ($trail ? ' trail' : '') . (($objSubpage->cssClass != '') ? ' ' . $objSubpage->cssClass : '');

					// Mark pages on the same level (see #2419)
					if ($objSubpage->pid == $objPage->pid)
					{
						$strClass .= ' sibling';
					}

					$row['isActive'] = false;
					$row['isTrail'] = $trail;
				}

				$row['subitems'] = $subitems;
				$row['class'] = trim($strClass);
				$row['title'] = \StringUtil::specialchars($objSubpage->title, true);
				$row['pageTitle'] = \StringUtil::specialchars($objSubpage->pageTitle, true);
				$row['link'] = $objSubpage->title;
				$row['href'] = $href;
				$row['nofollow'] = (strncmp($objSubpage->robots, 'noindex,nofollow', 16) === 0);
				$row['target'] = '';
				$row['description'] = str_replace(array("\n", "\r"), array(' ' , ''), $objSubpage->description);

				// Override the link target
				if ($objSubpage->type == 'redirect' && $objSubpage->target)
				{
					$row['target'] = ' target="_blank"';
				}

				$items[] = $row;
			}
		}

		// Add classes first and last
		if (!empty($items))
		{
			$last = count($items) - 1;

			$items[0]['class'] = trim($items[0]['class'] . ' first');
			$items[$last]['class'] = trim($items[$last]['class'] . ' last');
		}

		$objTemplate->items = $items;

		return !empty($items) ? $objTemplate->parse() : '';
	}


	/**
	 * Find a front end module in the FE_MOD array and return the class name
	 *
	 * @param string $strName The front end module name
	 *
	 * @return string The class name
	 */
	public static function findClass($strName)
	{
		foreach ($GLOBALS['FE_MOD'] as $v)
		{
			foreach ($v as $kk=>$vv)
			{
				if ($kk == $strName)
				{
					return $vv;
				}
			}
		}

		return '';
	}
}
