<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Menu\FrontendMenuBuilder;
use Knp\Menu\ItemInterface;

/**
 * Front end module "custom navigation".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleCustomnav extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_customnav';

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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['customnav'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Always return an array (see #4616)
		$this->pages = StringUtil::deserialize($this->pages, true);

		if (empty($this->pages) || !$this->pages[0])
		{
			return '';
		}

		$strBuffer = parent::generate();

		return $this->Template->items ? $strBuffer : '';
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var FrontendMenuBuilder $menuBuilder */
		$menuBuilder = System::getContainer()->get('contao.menu.frontend_builder');
		$root = System::getContainer()->get('knp_menu.factory')->createItem('root');

		$menu = $menuBuilder->getMenu($root, 0, $this->arrData);

		// Return if there are no pages
		if (!$menu->count())
		{
			return;
		}

		$items = array();

		$objTemplate = new FrontendTemplate($this->navigationTpl ?: 'nav_default');
		$objTemplate->type = static::class;
		$objTemplate->cssID = $this->cssID; // see #4897 and 6129
		$objTemplate->level = 'level_1';
		$objTemplate->module = $this; // see #155

		/** @var ItemInterface $menuItem */
		foreach ($menu as $menuItem)
		{
			$items[] = $this->compileMenuItem($menuItem);
		}

		// Add classes first and last if there are items
		if (!empty($items))
		{
			$items[0]['class'] = trim($items[0]['class'] . ' first');
			$last = \count($items) - 1;
			$items[$last]['class'] = trim($items[$last]['class'] . ' last');
		}

		$objTemplate->items = $items;

		$this->Template->request = Environment::get('indexFreeRequest');
		$this->Template->skipId = 'skipNavigation' . $this->id;
		$this->Template->skipNavigation = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['skipNavigation']);
		$this->Template->items = !empty($items) ? $objTemplate->parse() : '';
	}
}

class_alias(ModuleCustomnav::class, 'ModuleCustomnav');
