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

trigger_deprecation('contao/core-bundle', '5.1', sprintf('The "%s" has been deprecated and will be removed in Contao 6. Use the Feed Reader Module instead.', __CLASS__));

/**
 * Front end module "rss reader".
 */
class ModuleRssReader extends Module
{
	/**
	 * RSS feed
	 * @var \SimplePie
	 */
	protected $objFeed;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'rss_default';

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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['rssReader'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->objFeed = new \SimplePie();
		$arrUrls = StringUtil::trimsplit('[\n\t ]', trim($this->rss_feed));

		if (\count($arrUrls) > 1)
		{
			$this->objFeed->set_feed_url($arrUrls);
		}
		else
		{
			$this->objFeed->set_feed_url($arrUrls[0]);
		}

		$this->objFeed->set_output_encoding(System::getContainer()->getParameter('kernel.charset'));
		$this->objFeed->set_cache_location(System::getContainer()->getParameter('kernel.project_dir') . '/system/tmp');
		$this->objFeed->enable_cache(false);

		if ($this->rss_cache > 0)
		{
			$this->objFeed->enable_cache();
			$this->objFeed->set_cache_duration($this->rss_cache);
		}

		if (!$this->objFeed->init())
		{
			System::getContainer()->get('monolog.logger.contao.error')->error('Error importing RSS feed "' . $this->rss_feed . '"');

			return '';
		}

		$this->objFeed->handle_content_type();

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		if ($this->rss_template != 'rss_default')
		{
			$this->strTemplate = $this->rss_template;

			$this->Template = new FrontendTemplate($this->strTemplate);
			$this->Template->setData($this->arrData);
		}

		$this->Template->link = $this->objFeed->get_link();
		$this->Template->title = $this->objFeed->get_title();
		$this->Template->language = $this->objFeed->get_language();
		$this->Template->description = $this->objFeed->get_description();
		$this->Template->copyright = $this->objFeed->get_copyright();

		// Add image
		if ($this->objFeed->get_image_url())
		{
			$this->Template->image = true;
			$this->Template->src = $this->objFeed->get_image_url();
			$this->Template->alt = $this->objFeed->get_image_title();
			$this->Template->href = $this->objFeed->get_image_link();
			$this->Template->height = $this->objFeed->get_image_height();
			$this->Template->width = $this->objFeed->get_image_width();
		}

		// Get the items (see #6107)
		$arrItems = \array_slice($this->objFeed->get_items(0, $this->numberOfItems + $this->skipFirst), $this->skipFirst, $this->numberOfItems ?: null);

		$limit = \count($arrItems);
		$offset = 0;

		// Split pages
		if ($this->perPage > 0)
		{
			// Get the current page
			$id = 'page_r' . $this->id;
			$page = (int) (Input::get($id) ?? 1);

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil(\count($arrItems)/$this->perPage), 1))
			{
				throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
			}

			// Set limit and offset
			$offset = (($page - 1) * $this->perPage);
			$limit = $this->perPage + $offset;

			$objPagination = new Pagination(\count($arrItems), $this->perPage, Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$items = array();

		/** @var \SimplePie_Item[] $arrItems */
		for ($i=$offset, $c=\count($arrItems); $i<$limit && $i<$c; $i++)
		{
			$items[$i] = array
			(
				'link' => $arrItems[$i]->get_link(),
				'title' => $arrItems[$i]->get_title(),
				'permalink' => $arrItems[$i]->get_permalink(),
				'description' => str_replace(array('<?', '?>'), array('&lt;?', '?&gt;'), $arrItems[$i]->get_description()),
				'pubdate' => Date::parse($objPage->datimFormat, $arrItems[$i]->get_date('U')),
				'category' => $arrItems[$i]->get_category(),
				'object' => $arrItems[$i]
			);

			// Add author
			if ($objAuthor = $arrItems[$i]->get_author())
			{
				$items[$i]['author'] = trim($objAuthor->name . ' ' . $objAuthor->email);
			}

			// Add enclosure
			if ($objEnclosure = $arrItems[$i]->get_enclosure())
			{
				$items[$i]['enclosure'] = $objEnclosure->get_link();
			}
		}

		$this->Template->items = array_values($items);
	}
}
