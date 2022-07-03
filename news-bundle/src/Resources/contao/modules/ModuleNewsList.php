<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Model\Collection;

/**
 * Front end module "news list".
 *
 * @property array  $news_archives
 * @property string $news_featured
 * @property string $news_order
 */
class ModuleNewsList extends ModuleNews
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_newslist';

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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['newslist'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->news_archives = $this->sortOutProtected(StringUtil::deserialize($this->news_archives));

		// Return if there are no archives
		if (empty($this->news_archives) || !\is_array($this->news_archives))
		{
			return '';
		}

		// Show the newsreader if an item has been selected
		if ($this->news_readerModule > 0 && Input::get('auto_item') !== null)
		{
			return $this->getFrontendModule($this->news_readerModule, $this->strColumn);
		}

		// Tag the news archives (see #2137)
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags(array_map(static function ($id) { return 'contao.db.tl_news_archive.' . $id; }, $this->news_archives));
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$limit = null;
		$offset = (int) $this->skipFirst;

		// Maximum number of items
		if ($this->numberOfItems > 0)
		{
			$limit = $this->numberOfItems;
		}

		// Handle featured news
		if ($this->news_featured == 'featured')
		{
			$blnFeatured = true;
		}
		elseif ($this->news_featured == 'unfeatured')
		{
			$blnFeatured = false;
		}
		else
		{
			$blnFeatured = null;
		}

		$this->Template->articles = array();
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

		// Get the total number of items
		$intTotal = $this->countItems($this->news_archives, $blnFeatured);

		if ($intTotal < 1)
		{
			return;
		}

		$total = $intTotal - $offset;

		// Split the results
		if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage))
		{
			// Adjust the overall limit
			if (isset($limit))
			{
				$total = min($limit, $total);
			}

			// Get the current page
			$id = 'page_n' . $this->id;
			$page = Input::get($id) ?? 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
			{
				throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
			}

			// Set limit and offset
			$limit = $this->perPage;
			$offset += (max($page, 1) - 1) * $this->perPage;
			$skip = (int) $this->skipFirst;

			// Overall limit
			if ($offset + $limit > $total + $skip)
			{
				$limit = $total + $skip - $offset;
			}

			// Add the pagination menu
			$objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$objArticles = $this->fetchItems($this->news_archives, $blnFeatured, ($limit ?: 0), $offset);

		// Add the articles
		if ($objArticles !== null)
		{
			$this->Template->articles = $this->parseArticles($objArticles);
		}

		$this->Template->archives = $this->news_archives;
	}

	/**
	 * Count the total matching items
	 *
	 * @param array   $newsArchives
	 * @param boolean $blnFeatured
	 *
	 * @return integer
	 */
	protected function countItems($newsArchives, $blnFeatured)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['newsListCountItems']) && \is_array($GLOBALS['TL_HOOKS']['newsListCountItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['newsListCountItems'] as $callback)
			{
				if (($intResult = System::importStatic($callback[0])->{$callback[1]}($newsArchives, $blnFeatured, $this)) === false)
				{
					continue;
				}

				if (\is_int($intResult))
				{
					return $intResult;
				}
			}
		}

		return NewsModel::countPublishedByPids($newsArchives, $blnFeatured);
	}

	/**
	 * Fetch the matching items
	 *
	 * @param array   $newsArchives
	 * @param boolean $blnFeatured
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return Collection|NewsModel|null
	 */
	protected function fetchItems($newsArchives, $blnFeatured, $limit, $offset)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['newsListFetchItems']) && \is_array($GLOBALS['TL_HOOKS']['newsListFetchItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['newsListFetchItems'] as $callback)
			{
				if (($objCollection = System::importStatic($callback[0])->{$callback[1]}($newsArchives, $blnFeatured, $limit, $offset, $this)) === false)
				{
					continue;
				}

				if ($objCollection === null || $objCollection instanceof Collection)
				{
					return $objCollection;
				}
			}
		}

		// Determine sorting
		$t = NewsModel::getTable();
		$order = '';

		if ($this->news_featured == 'featured_first')
		{
			$order .= "$t.featured DESC, ";
		}

		switch ($this->news_order)
		{
			case 'order_headline_asc':
				$order .= "$t.headline";
				break;

			case 'order_headline_desc':
				$order .= "$t.headline DESC";
				break;

			case 'order_random':
				$order .= "RAND()";
				break;

			case 'order_date_asc':
				$order .= "$t.date";
				break;

			default:
				$order .= "$t.date DESC";
		}

		return NewsModel::findPublishedByPids($newsArchives, $blnFeatured, $limit, $offset, array('order'=>$order));
	}
}
