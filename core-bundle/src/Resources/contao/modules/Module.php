<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Model\Collection;
use Symfony\Component\Routing\Exception\ExceptionInterface;

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
 * @property array   $pages
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
 * @property string  $contextLength
 * @property integer $minKeywordLength
 * @property integer $perPage
 * @property string  $searchType
 * @property string  $searchTpl
 * @property string  $inColumn
 * @property integer $skipFirst
 * @property boolean $loadFirst
 * @property string  $singleSRC
 * @property string  $url
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
abstract class Module extends Frontend
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
		if ($objModule instanceof Model || $objModule instanceof Collection)
		{
			/** @var ModuleModel $objModel */
			$objModel = $objModule;

			if ($objModel instanceof Collection)
			{
				$objModel = $objModel->current();
			}

			$this->objModel = $objModel;
		}

		parent::__construct();

		$this->arrData = $objModule->row();
		$this->cssID = StringUtil::deserialize($objModule->cssID, true);

		if ($this->customTpl)
		{
			$request = System::getContainer()->get('request_stack')->getCurrentRequest();

			// Use the custom template unless it is a back end request
			if (!$request || !System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
			{
				$this->strTemplate = $this->customTpl;
			}
		}

		$arrHeadline = StringUtil::deserialize($objModule->headline);
		$this->headline = \is_array($arrHeadline) ? $arrHeadline['value'] : $arrHeadline;
		$this->hl = \is_array($arrHeadline) ? $arrHeadline['unit'] : 'h1';
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
		return $this->arrData[$strKey] ?? parent::__get($strKey);
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
		$this->Template = new FrontendTemplate($this->strTemplate);
		$this->Template->setData($this->arrData);

		$this->compile();

		// Do not change this order (see #6191)
		$this->Template->style = !empty($this->arrStyle) ? implode(' ', $this->arrStyle) : '';
		$this->Template->class = trim('mod_' . $this->type . ' ' . ($this->cssID[1] ?? ''));
		$this->Template->cssID = !empty($this->cssID[0]) ? ' id="' . $this->cssID[0] . '"' : '';

		$this->Template->inColumn = $this->strColumn;

		if (!$this->Template->headline)
		{
			$this->Template->headline = $this->headline;
		}

		if (!$this->Template->hl)
		{
			$this->Template->hl = $this->hl;
		}

		if (!empty($this->objModel->classes) && \is_array($this->objModel->classes))
		{
			$this->Template->class .= ' ' . implode(' ', $this->objModel->classes);
		}

		// Tag the module (see #2137)
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger') && !empty($tags = $this->getResponseCacheTags()))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags($tags);
		}

		return $this->Template->parse();
	}

	/**
	 * Compile the current element
	 */
	abstract protected function compile();

	/**
	 * Get a list of tags that should be applied to the response when calling generate().
	 */
	protected function getResponseCacheTags(): array
	{
		return array('contao.db.tl_module.' . $this->id);
	}

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
		$arrSubpages = static::getPublishedSubpagesWithoutGuestsByPid($pid, $this->showHidden, $this instanceof ModuleSitemap);

		if ($arrSubpages === null)
		{
			return '';
		}

		$items = array();
		$user = null;

		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser())
		{
			$user = FrontendUser::getInstance();
		}

		$objTemplate = new FrontendTemplate($this->navigationTpl ?: 'nav_default');
		$objTemplate->pid = $pid;
		$objTemplate->type = static::class;
		$objTemplate->cssID = $this->cssID; // see #4897
		$objTemplate->level = 'level_' . $level++;
		$objTemplate->module = $this; // see #155

		/** @var PageModel $objPage */
		global $objPage;

		// Browse subpages
		foreach ($arrSubpages as list('page' => $objSubpage, 'hasSubpages' => $blnHasSubpages))
		{
			// Skip hidden sitemap pages
			if ($this instanceof ModuleSitemap && $objSubpage->sitemap == 'map_never')
			{
				continue;
			}

			$subitems = '';

			// Override the domain (see #3765)
			if ($host !== null)
			{
				$objSubpage->domain = $host;
			}

			$groups = StringUtil::deserialize($objSubpage->groups, true);

			// Do not show protected pages unless a front end user is logged in
			if (!$objSubpage->protected || $this->showProtected || ($this instanceof ModuleSitemap && $objSubpage->sitemap == 'map_always') || (!$user && \in_array(-1, $groups)) || ($user && $user->isMemberOf($groups)))
			{
				// Check whether there will be subpages
				if ($blnHasSubpages && (!$this->showLevel || $this->showLevel >= $level || (!$this->hardLimit && ($objPage->id == $objSubpage->id || \in_array($objPage->id, $this->Database->getChildRecords($objSubpage->id, 'tl_page'))))))
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
							$href = StringUtil::encodeEmail($href);
						}
						break;

					case 'forward':
						if ($objSubpage->jumpTo)
						{
							$objNext = PageModel::findPublishedById($objSubpage->jumpTo);
						}
						else
						{
							$objNext = PageModel::findFirstPublishedRegularByPid($objSubpage->id);
						}

						// Hide the link if the target page is invisible
						if (!$objNext instanceof PageModel || (!$objNext->loadDetails()->isPublic && !BE_USER_LOGGED_IN))
						{
							continue 2;
						}

						try
						{
							$href = $objNext->getFrontendUrl();
						}
						catch (ExceptionInterface $exception)
						{
							System::log('Unable to generate URL for page ID ' . $objSubpage->id . ': ' . $exception->getMessage(), __METHOD__, TL_ERROR);

							continue 2;
						}
						break;

					default:
						try
						{
							$href = $objSubpage->getFrontendUrl();
						}
						catch (ExceptionInterface $exception)
						{
							System::log('Unable to generate URL for page ID ' . $objSubpage->id . ': ' . $exception->getMessage(), __METHOD__, TL_ERROR);

							continue 2;
						}
						break;
				}

				$items[] = $this->compileNavigationRow($objPage, $objSubpage, $subitems, $href);
			}
		}

		// Add classes first and last
		if (!empty($items))
		{
			$last = \count($items) - 1;

			$items[0]['class'] = trim($items[0]['class'] . ' first');
			$items[$last]['class'] = trim($items[$last]['class'] . ' last');
		}

		$objTemplate->items = $items;

		return !empty($items) ? $objTemplate->parse() : '';
	}

	/**
	 * Compile the navigation row and return it as array
	 *
	 * @param PageModel $objPage
	 * @param PageModel $objSubpage
	 * @param string    $subitems
	 * @param string    $href
	 *
	 * @return array
	 */
	protected function compileNavigationRow(PageModel $objPage, PageModel $objSubpage, $subitems, $href)
	{
		$row = $objSubpage->row();
		$trail = \in_array($objSubpage->id, $objPage->trail);

		// Use the path without query string to check for active pages (see #480)
		list($path) = explode('?', Environment::get('request'), 2);

		// Active page
		if (($objPage->id == $objSubpage->id || ($objSubpage->type == 'forward' && $objPage->id == $objSubpage->jumpTo)) && !($this instanceof ModuleSitemap) && $href == $path)
		{
			// Mark active forward pages (see #4822)
			$strClass = (($objSubpage->type == 'forward' && $objPage->id == $objSubpage->jumpTo) ? 'forward' . ($trail ? ' trail' : '') : 'active') . ($subitems ? ' submenu' : '') . ($objSubpage->protected ? ' protected' : '') . ($objSubpage->cssClass ? ' ' . $objSubpage->cssClass : '');

			$row['isActive'] = true;
			$row['isTrail'] = false;
		}

		// Regular page
		else
		{
			$strClass = ($subitems ? 'submenu' : '') . ($objSubpage->protected ? ' protected' : '') . ($trail ? ' trail' : '') . ($objSubpage->cssClass ? ' ' . $objSubpage->cssClass : '');

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
		$row['title'] = StringUtil::specialchars($objSubpage->title, true);
		$row['pageTitle'] = StringUtil::specialchars($objSubpage->pageTitle, true);
		$row['link'] = $objSubpage->title;
		$row['href'] = $href;
		$row['rel'] = '';
		$row['nofollow'] = (strncmp($objSubpage->robots, 'noindex,nofollow', 16) === 0); // backwards compatibility
		$row['target'] = '';
		$row['description'] = str_replace(array("\n", "\r"), array(' ', ''), $objSubpage->description);

		$arrRel = array();

		if (strncmp($objSubpage->robots, 'noindex,nofollow', 16) === 0)
		{
			$arrRel[] = 'nofollow';
		}

		// Override the link target
		if ($objSubpage->type == 'redirect' && $objSubpage->target)
		{
			$arrRel[] = 'noreferrer';
			$arrRel[] = 'noopener';

			$row['target'] = ' target="_blank"';
		}

		// Set the rel attribute
		if (!empty($arrRel))
		{
			$row['rel'] = ' rel="' . implode(' ', $arrRel) . '"';
		}

		return $row;
	}

	/**
	 * Get all published pages by their parent ID and exclude pages only visible for guests
	 *
	 * @param integer $intPid        The parent page's ID
	 * @param boolean $blnShowHidden If true, hidden pages will be included
	 * @param boolean $blnIsSitemap  If true, the sitemap settings apply
	 *
	 * @return array<array{page:PageModel,hasSubpages:bool}>|null
	 */
	protected static function getPublishedSubpagesWithoutGuestsByPid($intPid, $blnShowHidden=false, $blnIsSitemap=false): ?array
	{
		$time = Date::floorToMinute();
		$tokenChecker = System::getContainer()->get('contao.security.token_checker');
		$blnFeUserLoggedIn = $tokenChecker->hasFrontendUser();
		$blnBeUserLoggedIn = $tokenChecker->hasBackendUser() && $tokenChecker->isPreviewMode();

		$arrPages = Database::getInstance()->prepare("SELECT p1.id, EXISTS(SELECT * FROM tl_page p2 WHERE p2.pid=p1.id AND p2.type!='root' AND p2.type!='error_401' AND p2.type!='error_403' AND p2.type!='error_404'" . (!$blnShowHidden ? ($blnIsSitemap ? " AND (p2.hide='' OR sitemap='map_always')" : " AND p2.hide=''") : "") . ($blnFeUserLoggedIn ? " AND p2.guests=''" : "") . (!$blnBeUserLoggedIn ? " AND p2.published='1' AND (p2.start='' OR p2.start<='$time') AND (p2.stop='' OR p2.stop>'$time')" : "") . ") AS hasSubpages FROM tl_page p1 WHERE p1.pid=? AND p1.type!='root' AND p1.type!='error_401' AND p1.type!='error_403' AND p1.type!='error_404'" . (!$blnShowHidden ? ($blnIsSitemap ? " AND (p1.hide='' OR sitemap='map_always')" : " AND p1.hide=''") : "") . ($blnFeUserLoggedIn ? " AND p1.guests=''" : "") . (!$blnBeUserLoggedIn ? " AND p1.published='1' AND (p1.start='' OR p1.start<='$time') AND (p1.stop='' OR p1.stop>'$time')" : "") . " ORDER BY p1.sorting")
										   ->execute($intPid)
										   ->fetchAllAssoc();

		if (\count($arrPages) < 1)
		{
			return null;
		}

		// Load models into the registry with a single query
		PageModel::findMultipleByIds(array_map(static function ($row) { return $row['id']; }, $arrPages));

		return array_map(
			static function (array $row): array
			{
				return array(
					'page' => PageModel::findByPk($row['id']),
					'hasSubpages' => (bool) $row['hasSubpages'],
				);
			},
			$arrPages
		);
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

class_alias(Module::class, 'Module');
