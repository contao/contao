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
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Front end module "search".
 */
class ModuleSearch extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_search';

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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['search'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->pages = StringUtil::deserialize($this->pages);

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		// Trigger the search module from a custom form
		if (Input::get('keywords') === null && Input::post('FORM_SUBMIT') == 'tl_search')
		{
			Input::setGet('keywords', Input::post('keywords'));
			Input::setGet('query_type', Input::post('query_type'));
			Input::setGet('per_page', Input::post('per_page'));
		}

		$blnFuzzy = $this->fuzzy;
		$strQueryType = Input::get('query_type') ?: $this->queryType;

		if (\is_array(Input::get('keywords')))
		{
			throw new BadRequestHttpException('Expected string, got array');
		}

		$strKeywords = trim(Input::get('keywords'));

		$this->Template->uniqueId = $this->id;
		$this->Template->queryType = $strQueryType;
		$this->Template->keyword = StringUtil::specialchars($strKeywords);
		$this->Template->keywordLabel = $GLOBALS['TL_LANG']['MSC']['keywords'];
		$this->Template->optionsLabel = $GLOBALS['TL_LANG']['MSC']['options'];
		$this->Template->search = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['searchLabel']);
		$this->Template->matchAll = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['matchAll']);
		$this->Template->matchAny = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['matchAny']);
		$this->Template->advanced = ($this->searchType == 'advanced');

		// Redirect page
		if (($objTarget = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$this->Template->action = $objTarget->getFrontendUrl();
		}

		$this->Template->pagination = '';
		$this->Template->results = '';

		// Execute the search if there are keywords
		if ($strKeywords !== '' && $strKeywords != '*' && !$this->jumpTo)
		{
			// Search pages
			if (!empty($this->pages) && \is_array($this->pages))
			{
				$arrPages = array();

				foreach ($this->pages as $intPageId)
				{
					$arrPages[] = array($intPageId);
					$arrPages[] = $this->Database->getChildRecords($intPageId, 'tl_page');
				}

				if (!empty($arrPages))
				{
					$arrPages = array_merge(...$arrPages);
				}

				$arrPages = array_unique($arrPages);
			}
			// Website root
			else
			{
				/** @var PageModel $objPage */
				global $objPage;

				$arrPages = $this->Database->getChildRecords($objPage->rootId, 'tl_page');
			}

			// HOOK: add custom logic (see #5223)
			if (isset($GLOBALS['TL_HOOKS']['customizeSearch']) && \is_array($GLOBALS['TL_HOOKS']['customizeSearch']))
			{
				foreach ($GLOBALS['TL_HOOKS']['customizeSearch'] as $callback)
				{
					$this->import($callback[0]);
					$this->{$callback[0]}->{$callback[1]}($arrPages, $strKeywords, $strQueryType, $blnFuzzy, $this, $objResult, $arrResult);
				}
			}

			// Return if there are no pages
			if (empty($arrPages) || !\is_array($arrPages))
			{
				return;
			}

			$query_starttime = microtime(true);

			try
			{
				if (is_null($objResult))
				{
					$objResult = Search::query($strKeywords, $strQueryType == 'or', $arrPages, $blnFuzzy, $this->minKeywordLength);
				}
			}
			catch (\Exception $e)
			{
				System::getContainer()->get('monolog.logger.contao.error')->error('Website search failed: ' . $e->getMessage());

				$objResult = new SearchResult(array());
			}

			$query_endtime = microtime(true);

			// Sort out protected pages
			if (System::getContainer()->getParameter('contao.search.index_protected'))
			{
				$objResult->applyFilter(static function ($v) {
					return empty($v['protected']) || System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, StringUtil::deserialize($v['groups'] ?? null, true));
				});
			}

			if(is_null($arrResult))
			{
				$count = $objResult->getCount();
			}
			else
			{
				$count = count($arrResult);
			}

			$this->Template->count = $count;
			$this->Template->page = null;
			$this->Template->keywords = $strKeywords;

			if ($this->minKeywordLength > 0)
			{
				$this->Template->keywordHint = sprintf($GLOBALS['TL_LANG']['MSC']['sKeywordHint'], $this->minKeywordLength);
			}

			// No results
			if ($count < 1)
			{
				$this->Template->header = sprintf($GLOBALS['TL_LANG']['MSC']['sEmpty'], $strKeywords);
				$this->Template->duration = System::getFormattedNumber($query_endtime - $query_starttime, 3) . ' ' . $GLOBALS['TL_LANG']['MSC']['seconds'];

				return;
			}

			$from = 1;
			$to = $count;

			// Pagination
			if ($this->perPage > 0)
			{
				$id = 'page_s' . $this->id;
				$page = (int) (Input::get($id) ?? 1);
				$per_page = (int) Input::get('per_page') ?: $this->perPage;

				// Do not index or cache the page if the page number is outside the range
				if ($page < 1 || $page > max(ceil($count/$per_page), 1))
				{
					throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
				}

				$from = (($page - 1) * $per_page) + 1;
				$to = (($from + $per_page) > $count) ? $count : ($from + $per_page - 1);

				// Pagination menu
				if ($to < $count || $from > 1)
				{
					$objPagination = new Pagination($count, $per_page, Config::get('maxPaginationLinks'), $id);
					$this->Template->pagination = $objPagination->generate("\n  ");
				}

				$this->Template->page = $page;
			}

			$contextLength = 48;
			$totalLength = 360;

			$lengths = StringUtil::deserialize($this->contextLength, true) + array(null, null);

			if ($lengths[0] > 0)
			{
				$contextLength = $lengths[0];
			}

			if ($lengths[1] > 0)
			{
				$totalLength = $lengths[1];
			}

			if(is_null($arrResult))
			{
				$arrResult = $objResult->getResults($to-$from+1, $from-1);
			}

			// Get the results
			foreach (array_keys($arrResult) as $i)
			{
				$objTemplate = new FrontendTemplate($this->searchTpl ?: 'search_default');
				$objTemplate->setData($arrResult[$i]);
				$objTemplate->href = $arrResult[$i]['url'];
				$objTemplate->link = $arrResult[$i]['title'];
				$objTemplate->url = StringUtil::specialchars(urldecode($arrResult[$i]['url']), true, true);
				$objTemplate->title = StringUtil::specialchars(StringUtil::stripInsertTags($arrResult[$i]['title']));
				$objTemplate->relevance = sprintf($GLOBALS['TL_LANG']['MSC']['relevance'], number_format($arrResult[$i]['relevance'] / $arrResult[0]['relevance'] * 100, 2) . '%');
				$objTemplate->unit = $GLOBALS['TL_LANG']['UNITS'][1];

				$arrContext = array();
				$strText = StringUtil::stripInsertTags(strtok($arrResult[$i]['text'], "\n"));
				$arrMatches = Search::getMatchVariants(StringUtil::trimsplit(',', $arrResult[$i]['matches']), $strText, $GLOBALS['TL_LANGUAGE']);

				// Get the context
				foreach ($arrMatches as $strWord)
				{
					$arrChunks = array();
					preg_match_all('/(^|\b.{0,' . $contextLength . '}(?:\PL|\p{Hiragana}|\p{Katakana}|\p{Han}|\p{Myanmar}|\p{Khmer}|\p{Lao}|\p{Thai}|\p{Tibetan}))' . preg_quote($strWord, '/') . '((?:\PL|\p{Hiragana}|\p{Katakana}|\p{Han}|\p{Myanmar}|\p{Khmer}|\p{Lao}|\p{Thai}|\p{Tibetan}).{0,' . $contextLength . '}\b|$)/ui', $strText, $arrChunks);

					foreach ($arrChunks[0] as $strContext)
					{
						$arrContext[] = ' ' . $strContext . ' ';
					}

					// Skip other terms if the total length is already reached
					if (array_sum(array_map('mb_strlen', $arrContext)) >= $totalLength)
					{
						break;
					}
				}

				// Shorten the context and highlight all keywords
				if (!empty($arrContext))
				{
					$objTemplate->context = trim(StringUtil::substrHtml(implode('…', $arrContext), $totalLength));
					$objTemplate->context = preg_replace('((?<=^|\PL|\p{Hiragana}|\p{Katakana}|\p{Han}|\p{Myanmar}|\p{Khmer}|\p{Lao}|\p{Thai}|\p{Tibetan})(' . implode('|', array_map('preg_quote', $arrMatches)) . ')(?=\PL|\p{Hiragana}|\p{Katakana}|\p{Han}|\p{Myanmar}|\p{Khmer}|\p{Lao}|\p{Thai}|\p{Tibetan}|$))ui', '<mark class="highlight">$1</mark>', $objTemplate->context);

					$objTemplate->hasContext = true;
				}

				$this->addImageToTemplateFromSearchResult($arrResult[$i], $objTemplate);

				$this->Template->results .= $objTemplate->parse();
			}

			$this->Template->header = vsprintf($GLOBALS['TL_LANG']['MSC']['sResults'], array($from, $to, $count, $strKeywords));
			$this->Template->duration = System::getFormattedNumber($query_endtime - $query_starttime, 3) . ' ' . $GLOBALS['TL_LANG']['MSC']['seconds'];
		}
	}

	protected function addImageToTemplateFromSearchResult(array $result, Template $template): void
	{
		$template->hasImage = false;

		if (!isset($result['meta']))
		{
			return;
		}

		$meta = json_decode($result['meta'], true);

		foreach ($meta as $v)
		{
			if (!isset($v['https://schema.org/primaryImageOfPage']['contentUrl']))
			{
				continue;
			}

			$baseUrls = array_filter(array(Environment::get('base'), System::getContainer()->get('contao.assets.files_context')->getStaticUrl()));

			$figureBuilder = System::getContainer()->get('contao.image.studio')->createFigureBuilder();
			$figureBuilder->fromUrl($v['https://schema.org/primaryImageOfPage']['contentUrl'], $baseUrls);

			$figureMeta = new Metadata(array_filter(array(
				Metadata::VALUE_CAPTION => $v['https://schema.org/primaryImageOfPage']['caption'] ?? null,
				Metadata::VALUE_TITLE => $v['https://schema.org/primaryImageOfPage']['name'] ?? null,
				Metadata::VALUE_ALT => $v['https://schema.org/primaryImageOfPage']['alternateName'] ?? null,
			)));

			$figure = $figureBuilder
				->setSize($this->imgSize)
				->setMetadata($figureMeta)
				->setLinkHref($result['url'])
				->buildIfResourceExists();

			if (null === $figure)
			{
				continue;
			}

			$template->hasImage = true;
			$template->figure = $figure;
			$template->image = (object) $figure->getLegacyTemplateData();

			return;
		}
	}
}
