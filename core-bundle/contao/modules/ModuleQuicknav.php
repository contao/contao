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
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * Front end module "quick navigation".
 */
class ModuleQuicknav extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_quicknav';

	/**
	 * Redirect to the selected page
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['quicknav'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		if (Input::post('FORM_SUBMIT') == 'tl_quicknav_' . $this->id)
		{
			$this->redirect(Input::post('target', true));
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$host = null;

		// Start from the website root if there is no reference page
		if (!$this->rootPage)
		{
			$this->rootPage = $objPage->rootId;
		}

		// Overwrite the domain and language if the reference page belongs to a different root page (see #3765)
		else
		{
			$objRootPage = PageModel::findWithDetails($this->rootPage);

			// Set the domain
			if ($objRootPage->rootId != $objPage->rootId && $objRootPage->domain && $objRootPage->domain != $objPage->domain)
			{
				$host = $objRootPage->domain;
			}
		}

		$this->Template->formId = 'tl_quicknav_' . $this->id;
		$this->Template->targetPage = $GLOBALS['TL_LANG']['MSC']['targetPage'];
		$this->Template->button = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['go']);
		$this->Template->title = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['quicknav'];
		$this->Template->items = $this->getQuicknavPages($this->rootPage, 1, $host);
	}

	/**
	 * Recursively get all quicknav pages and return them as array
	 *
	 * @param integer $pid
	 * @param integer $level
	 * @param string  $host
	 *
	 * @return array
	 */
	protected function getQuicknavPages($pid, $level=1, $host=null)
	{
		/** @var PageModel $objPage */
		global $objPage;

		$arrPages = array();

		// Get all active subpages
		$objSubpages = PageModel::findPublishedRegularByPid($pid);

		if ($objSubpages === null)
		{
			return array();
		}

		++$level;

		$container = System::getContainer();
		$db = Database::getInstance();
		$security = $container->get('security.helper');

		foreach ($objSubpages as $objSubpage)
		{
			$objSubpage->loadDetails();

			// Override the domain (see #3765)
			if ($host !== null)
			{
				$objSubpage->domain = $host;
			}

			// PageModel->groups is an array after calling loadDetails()
			if (!$objSubpage->protected || $this->showProtected || $security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $objSubpage->groups))
			{
				// Do not skip the current page here! (see #4523)

				// Check hidden pages
				if (!$objSubpage->hide || $this->showHidden)
				{
					try
					{
						$href = $objSubpage->getFrontendUrl();
					}
					catch (ExceptionInterface $exception)
					{
						continue;
					}

					$arrPages[] = array
					(
						'level' => ($level - 2),
						'title' => StringUtil::specialchars(StringUtil::stripInsertTags($objSubpage->pageTitle ?: $objSubpage->title)),
						'href' => $href,
						'link' => StringUtil::stripInsertTags($objSubpage->title),
						'active' => ($objPage->id == $objSubpage->id || ($objSubpage->type == 'forward' && $objPage->id == $objSubpage->jumpTo))
					);

					// Subpages
					if (!$this->showLevel || $this->showLevel >= $level || (!$this->hardLimit && ($objPage->id == $objSubpage->id || \in_array($objPage->id, $db->getChildRecords($objSubpage->id, 'tl_page')))))
					{
						$subpages = $this->getQuicknavPages($objSubpage->id, $level);

						if (\is_array($subpages))
						{
							$arrPages = array_merge($arrPages, $subpages);
						}
					}
				}
			}
		}

		return $arrPages;
	}
}
