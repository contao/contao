<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Patchwork\Utf8;

/**
 * Front end module "quick navigation".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['quicknav'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

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

		$lang = null;
		$host = null;

		// Start from the website root if there is no reference page
		if (!$this->rootPage)
		{
			$this->rootPage = $objPage->rootId;
		}

		// Overwrite the domain and language if the reference page belongs to a differnt root page (see #3765)
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

		$user = null;
		$arrPages = array();

		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser())
		{
			$user = FrontendUser::getInstance();
		}

		// Get all active subpages
		$objSubpages = PageModel::findPublishedRegularByPid($pid);

		if ($objSubpages === null)
		{
			return array();
		}

		++$level;

		foreach ($objSubpages as $objSubpage)
		{
			$objSubpage->loadDetails();

			// Override the domain (see #3765)
			if ($host !== null)
			{
				$objSubpage->domain = $host;
			}

			// PageModel->groups is an array after calling loadDetails()
			if (!$objSubpage->protected || $this->showProtected || (!$user && \in_array(-1, $objSubpage->groups)) || ($user && $user->isMemberOf($objSubpage->groups)))
			{
				// Do not skip the current page here! (see #4523)

				// Check hidden pages
				if (!$objSubpage->hide || $this->showHidden)
				{
					$arrPages[] = array
					(
						'level' => ($level - 2),
						'title' => StringUtil::specialchars(StringUtil::stripInsertTags($objSubpage->pageTitle ?: $objSubpage->title)),
						'href' => $objSubpage->getFrontendUrl(),
						'link' => StringUtil::stripInsertTags($objSubpage->title),
						'active' => ($objPage->id == $objSubpage->id || ($objSubpage->type == 'forward' && $objPage->id == $objSubpage->jumpTo))
					);

					// Subpages
					if (!$this->showLevel || $this->showLevel >= $level || (!$this->hardLimit && ($objPage->id == $objSubpage->id || \in_array($objPage->id, $this->Database->getChildRecords($objSubpage->id, 'tl_page')))))
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

class_alias(ModuleQuicknav::class, 'ModuleQuicknav');
