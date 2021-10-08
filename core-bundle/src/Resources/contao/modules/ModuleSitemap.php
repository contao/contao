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
 * Front end module "sitemap".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleSitemap extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_sitemap';

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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['sitemap'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
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

			$lang = $objRootPage->rootLanguage;
			$host = $objRootPage->domain;
		}

		$this->showLevel = 0;
		$this->hardLimit = false;
		$this->levelOffset = 0;

		$this->Template->items = $this->renderNavigation($this->rootPage, 1, $host, $lang);
	}
}

class_alias(ModuleSitemap::class, 'ModuleSitemap');
