<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Security\ContaoCorePermissions;

/**
 * Front end module "book navigation".
 */
class ModuleBooknav extends Module
{
	/**
	 * Pages array
	 * @var PageModel[]
	 */
	protected $arrPages = array();

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_booknav';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['booknav'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id));

			return $objTemplate->parse();
		}

		$objPage = System::getContainer()->get('contao.routing.page_finder')->getCurrentPage();

		if (!$this->rootPage || !\in_array($this->rootPage, $objPage->trail))
		{
			return '';
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		// Get the root page
		if (!$objTarget = PageModel::findById($this->objModel->rootPage))
		{
			return;
		}

		$groups = array();
		$user = System::getContainer()->get('security.helper')->getUser();

		// Get all groups of the current front end user
		if ($user instanceof FrontendUser)
		{
			$groups = $user->groups;
		}

		// Get all book pages
		$this->arrPages[$objTarget->id] = $objTarget;
		$this->getBookPages($objTarget->id, $groups, time());

		$objPage = System::getContainer()->get('contao.routing.page_finder')->getCurrentPage();

		// Upper page
		if ($objPage->id != $objTarget->id)
		{
			$intKey = $objPage->pid;

			// Skip forward pages (see #5074)
			while (isset($this->arrPages[$intKey]->pid) && $this->arrPages[$intKey]->type == 'forward')
			{
				$intKey = $this->arrPages[$intKey]->pid;
			}

			// Hide the link if the reference page is a forward page (see #5374)
			if (isset($this->arrPages[$intKey]))
			{
				$this->Template->hasUp = true;
				$this->Template->upHref = $this->arrPages[$intKey]->getFrontendUrl();
				$this->Template->upTitle = StringUtil::stripInsertTags($this->arrPages[$intKey]->title);
				$this->Template->upPageTitle = StringUtil::stripInsertTags($this->arrPages[$intKey]->pageTitle);
				$this->Template->upLink = $GLOBALS['TL_LANG']['MSC']['up'];
			}
		}

		$arrLookup = array_keys($this->arrPages);
		$intCurrent = array_search($objPage->id, $arrLookup);

		if ($intCurrent === false)
		{
			return; // see #8665
		}

		// HOOK: add pagination info
		$this->Template->currentPage = $intCurrent;
		$this->Template->pageCount = \count($arrLookup);

		// Previous page
		if ($intCurrent > 0)
		{
			$current = $intCurrent;
			$intKey = $arrLookup[$current - 1];

			// Skip forward pages (see #5074)
			while (isset($this->arrPages[$intKey]) && $this->arrPages[$intKey]->type == 'forward' && isset($arrLookup[--$current]))
			{
				$intKey = $arrLookup[$current - 1] ?? null;
			}

			if ($intKey === null)
			{
				$this->Template->hasPrev = false;
			}
			else
			{
				$this->Template->hasPrev = true;
				$this->Template->prevHref = $this->arrPages[$intKey]->getFrontendUrl();
				$this->Template->prevTitle = StringUtil::stripInsertTags($this->arrPages[$intKey]->title);
				$this->Template->prevPageTitle = StringUtil::stripInsertTags($this->arrPages[$intKey]->pageTitle);
				$this->Template->prevLink = $this->arrPages[$intKey]->title;
			}
		}

		// Next page
		if ($intCurrent < (\count($arrLookup) - 1))
		{
			$current = $intCurrent;
			$intKey = $arrLookup[$current + 1];

			// Skip forward pages (see #5074)
			while ($this->arrPages[$intKey]->type == 'forward' && isset($arrLookup[++$current]))
			{
				$intKey = $arrLookup[$current + 1];
			}

			if ($intKey === null)
			{
				$this->Template->hasNext = false;
			}
			else
			{
				$this->Template->hasNext = true;
				$this->Template->nextHref = $this->arrPages[$intKey]->getFrontendUrl();
				$this->Template->nextTitle = StringUtil::stripInsertTags($this->arrPages[$intKey]->title);
				$this->Template->nextPageTitle = StringUtil::stripInsertTags($this->arrPages[$intKey]->pageTitle);
				$this->Template->nextLink = $this->arrPages[$intKey]->title;
			}
		}
	}

	/**
	 * Recursively get all book pages
	 *
	 * @param integer $intParentId
	 * @param array   $groups
	 * @param integer $time
	 */
	protected function getBookPages($intParentId, $groups, $time)
	{
		$arrPages = static::getPublishedSubpagesByPid($intParentId, $this->showHidden);

		if ($arrPages === null)
		{
			return;
		}

		$security = System::getContainer()->get('security.helper');
		$isMember = $security->isGranted('ROLE_MEMBER');

		foreach ($arrPages as list('page' => $objPage, 'hasSubpages' => $blnHasSubpages))
		{
			$objPage->loadDetails();

			// Hide the page if it is only visible to guests
			if ($objPage->guests && $isMember)
			{
				continue;
			}

			// PageModel->groups is an array after calling loadDetails()
			if (!$objPage->protected || $this->showProtected || $security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $objPage->groups))
			{
				$this->arrPages[$objPage->id] = $objPage;

				if ($blnHasSubpages)
				{
					$this->getBookPages($objPage->id, $groups, $time);
				}
			}
		}
	}
}
