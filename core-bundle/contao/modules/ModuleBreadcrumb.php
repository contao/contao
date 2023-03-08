<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * Front end module "breadcrumb".
 */
class ModuleBreadcrumb extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_breadcrumb';

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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['breadcrumb'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

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

		$type = null;
		$pageId = $objPage->id;
		$pages = array($objPage);
		$items = array();

		$blnShowUnpublished = System::getContainer()->get('contao.security.token_checker')->isPreviewMode();

		// Get all pages up to the root page
		$objPages = PageModel::findParentsById($objPage->pid);

		if ($objPages !== null)
		{
			while ($pageId > 0 && $type != 'root' && $objPages->next())
			{
				$type = $objPages->type;
				$pageId = $objPages->pid;
				$pages[] = $objPages->current();
			}
		}

		// Get the first active regular page and display it instead of the root page
		if ($type == 'root')
		{
			$objFirstPage = PageModel::findFirstPublishedByPid($objPages->id);

			$items[] = array
			(
				'isRoot'   => true,
				'isActive' => false,
				'href'     => (($objFirstPage !== null) ? $this->getPageFrontendUrl($objFirstPage) : Environment::get('base')),
				'title'    => StringUtil::specialchars($objPages->pageTitle ?: $objPages->title, true),
				'link'     => $objPages->title,
				'data'     => (($objFirstPage !== null) ? $objFirstPage->row() : array()),
			);

			array_pop($pages);
		}

		for ($i=(\count($pages)-1); $i>0; $i--)
		{
			// Skip pages that require an item (see #3450) and hidden or unpublished pages
			if ($pages[$i]->requireItem || ($pages[$i]->hide && !$this->showHidden) || (!$pages[$i]->published && !$blnShowUnpublished))
			{
				continue;
			}

			// Get href
			switch ($pages[$i]->type)
			{
				case 'redirect':
					$href = $pages[$i]->url;

					if (strncasecmp($href, 'mailto:', 7) === 0)
					{
						$href = StringUtil::encodeEmail($href);
					}
					break;

				case 'forward':
					if ($pages[$i]->jumpTo)
					{
						$objNext = PageModel::findPublishedById($pages[$i]->jumpTo);
					}
					else
					{
						$objNext = PageModel::findFirstPublishedRegularByPid($pages[$i]->id);
					}

					if ($objNext instanceof PageModel)
					{
						$href = $this->getPageFrontendUrl($objNext);
						break;
					}
					// no break

				default:
					$href = $this->getPageFrontendUrl($pages[$i]);
					break;
			}

			// Do not add non-root pages with an empty URL to the breadcrumbs
			if ($href)
			{
				$items[] = array
				(
					'isRoot'   => false,
					'isActive' => false,
					'href'     => $href,
					'title'    => StringUtil::specialchars($pages[$i]->pageTitle ?: $pages[$i]->title, true),
					'link'     => $pages[$i]->title,
					'data'     => $pages[$i]->row(),
				);
			}
		}

		// Only add active article(s) to the breadcrumbs if the current page does not require an item (see #3450)
		if (Input::get('articles') !== null && !$pages[0]->requireItem)
		{
			$items[] = array
			(
				'isRoot'   => false,
				'isActive' => false,
				'href'     => $this->getPageFrontendUrl($pages[0]),
				'title'    => StringUtil::specialchars($pages[0]->pageTitle ?: $pages[0]->title, true),
				'link'     => $pages[0]->title,
				'data'     => $pages[0]->row(),
			);

			list($strSection, $strArticle) = explode(':', Input::get('articles')) + array(null, null);

			if ($strArticle === null)
			{
				$strArticle = $strSection;
			}

			$objArticle = ArticleModel::findByIdOrAlias($strArticle);
			$strAlias = $objArticle->alias ?: $objArticle->id;

			if ($objArticle->inColumn != 'main')
			{
				$strAlias = $objArticle->inColumn . ':' . $strAlias;
			}

			if ($objArticle !== null)
			{
				$items[] = array
				(
					'isRoot'   => false,
					'isActive' => true,
					'href'     => $this->getPageFrontendUrl($pages[0], '/articles/' . $strAlias),
					'title'    => StringUtil::specialchars($objArticle->title, true),
					'link'     => $objArticle->title,
					'data'     => $objArticle->row(),
				);
			}
		}

		// Active page
		else
		{
			$items[] = array
			(
				'isRoot'   => false,
				'isActive' => true,
				// Use the current request without query string for the current page (see #3450)
				'href'     => strtok(Environment::get('request'), '?'),
				'title'    => StringUtil::specialchars($pages[0]->pageTitle ?: $pages[0]->title),
				'link'     => $pages[0]->title,
				'data'     => $pages[0]->row(),
			);
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['generateBreadcrumb']) && \is_array($GLOBALS['TL_HOOKS']['generateBreadcrumb']))
		{
			foreach ($GLOBALS['TL_HOOKS']['generateBreadcrumb'] as $callback)
			{
				$this->import($callback[0]);
				$items = $this->{$callback[0]}->{$callback[1]}($items, $this);
			}
		}

		$this->Template->getSchemaOrgData = static function () use ($items): array
		{
			$jsonLd = array(
				'@type' => 'BreadcrumbList',
				'itemListElement' => array()
			);

			$position = 0;
			$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

			foreach ($items as $item)
			{
				$jsonLd['itemListElement'][] = array(
					'@type' => 'ListItem',
					'position' => ++$position,
					'item' => array(
						'@id' => $item['href'],
						'name' => $htmlDecoder->inputEncodedToPlainText($item['link'])
					)
				);
			}

			return $jsonLd;
		};

		$this->Template->items = $items;

		// Tag the pages
		if (!System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			return;
		}

		$tags = array();

		foreach ($items as $item)
		{
			if (isset($item['data']['id']))
			{
				$tags[] = 'contao.db.tl_page.' . $item['data']['id'];
			}
		}

		if (!empty($tags))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags($tags);
		}
	}

	private function getPageFrontendUrl(PageModel $pageModel, $strParams=null)
	{
		try
		{
			return $pageModel->getFrontendUrl($strParams);
		}
		catch (ExceptionInterface $exception)
		{
			return '';
		}
	}
}
