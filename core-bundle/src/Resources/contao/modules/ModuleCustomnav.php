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
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['customnav'][0]) . ' ###';
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
		/** @var PageModel $objPage */
		global $objPage;

		$items = array();
		$user = null;

		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser())
		{
			$user = FrontendUser::getInstance();
		}

		// Get all active pages and also include root pages if the language is added to the URL (see #72)
		$objPages = PageModel::findPublishedRegularByIds($this->pages, array('includeRoot'=>true));

		// Return if there are no pages
		if ($objPages === null)
		{
			return;
		}

		$objTemplate = new FrontendTemplate($this->navigationTpl ?: 'nav_default');
		$objTemplate->type = static::class;
		$objTemplate->cssID = $this->cssID; // see #4897 and 6129
		$objTemplate->level = 'level_1';
		$objTemplate->module = $this; // see #155

		/** @var PageModel[] $objPages */
		foreach ($objPages as $objModel)
		{
			$objModel->loadDetails();

			// Hide the page if it is not protected and only visible to guests (backwards compatibility)
			if ($objModel->guests && !$objModel->protected && !$user)
			{
				trigger_deprecation('contao/core-bundle', '4.12', 'Using the "show to guests only" feature has been deprecated an will no longer work in Contao 5.0. Use the "protect page" function instead.');
				continue;
			}

			// PageModel->groups is an array after calling loadDetails()
			if (!$objModel->protected || $this->showProtected || (!$user && \in_array(-1, $objModel->groups)) || ($user && $user->isMemberOf($objModel->groups)))
			{
				// Get href
				switch ($objModel->type)
				{
					case 'redirect':
						$href = $objModel->url;
						break;

					case 'root':
						// Overwrite the alias to link to the empty URL or language URL (see #1641)
						$objModel->alias = 'index';
						$href = $objModel->getFrontendUrl();
						break;

					case 'forward':
						if ($objModel->jumpTo)
						{
							$objNext = PageModel::findPublishedById($objModel->jumpTo);
						}
						else
						{
							$objNext = PageModel::findFirstPublishedRegularByPid($objModel->id);
						}

						if ($objNext instanceof PageModel)
						{
							$href = $objNext->getFrontendUrl();
							break;
						}
						// no break

					default:
						$href = $objModel->getFrontendUrl();
						break;
				}

				$trail = \in_array($objModel->id, $objPage->trail);

				// Use the path without query string to check for active pages (see #480)
				list($path) = explode('?', Environment::get('request'), 2);

				// Active page
				if ($objPage->id == $objModel->id && $href == $path)
				{
					$strClass = trim($objModel->cssClass);
					$row = $objModel->row();

					$row['isActive'] = true;
					$row['isTrail'] = false;
					$row['class'] = trim('active ' . $strClass);
					$row['title'] = StringUtil::specialchars($objModel->title, true);
					$row['pageTitle'] = StringUtil::specialchars($objModel->pageTitle, true);
					$row['link'] = $objModel->title;
					$row['href'] = $href;
					$row['rel'] = '';
					$row['nofollow'] = (strncmp($objModel->robots, 'noindex,nofollow', 16) === 0);
					$row['target'] = '';
					$row['description'] = str_replace(array("\n", "\r"), array(' ', ''), $objModel->description);

					$arrRel = array();

					if (strncmp($objModel->robots, 'noindex,nofollow', 16) === 0)
					{
						$arrRel[] = 'nofollow';
					}

					// Override the link target
					if ($objModel->type == 'redirect' && $objModel->target)
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

					$items[] = $row;
				}

				// Regular page
				else
				{
					$strClass = trim($objModel->cssClass . ($trail ? ' trail' : ''));
					$row = $objModel->row();

					$row['isActive'] = false;
					$row['isTrail'] = $trail;
					$row['class'] = $strClass;
					$row['title'] = StringUtil::specialchars($objModel->title, true);
					$row['pageTitle'] = StringUtil::specialchars($objModel->pageTitle, true);
					$row['link'] = $objModel->title;
					$row['href'] = $href;
					$row['rel'] = '';
					$row['nofollow'] = (strncmp($objModel->robots, 'noindex,nofollow', 16) === 0);
					$row['target'] = '';
					$row['description'] = str_replace(array("\n", "\r"), array(' ', ''), $objModel->description);

					$arrRel = array();

					if (strncmp($objModel->robots, 'noindex,nofollow', 16) === 0)
					{
						$arrRel[] = 'nofollow';
					}

					// Override the link target
					if ($objModel->type == 'redirect' && $objModel->target)
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

					$items[] = $row;
				}
			}
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
