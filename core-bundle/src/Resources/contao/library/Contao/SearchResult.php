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
 * Stores result from the Search::query() method
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class SearchResult
{
	/**
	 * @var array
	 */
	private $arrResultsById = array();

	/**
	 * @var array
	 */
	private $arrKeywords = array();

	/**
	 * @var array
	 */
	private $arrWildcards = array();

	/**
	 * @var array
	 */
	private $arrPhrases = array();

	public function __construct(array $arrResults, array $arrKeywords = array(), array $arrWildcards = array(), array $arrPhrases = array())
	{
		foreach ($arrResults as $arrRow)
		{
			$this->arrResultsById[(int) $arrRow['id']] = $arrRow;
		}

		$this->arrKeywords = $arrKeywords;
		$this->arrWildcards = $arrWildcards;
		$this->arrPhrases = $arrPhrases;
	}

	public function applyFilter(\Closure $filter): void
	{
		foreach ($this->arrResultsById as $intId => $arrRow)
		{
			if (!$filter($arrRow))
			{
				unset($this->arrResultsById[$intId]);
			}
		}
	}

	public function getCount(): int
	{
		return \count($this->arrResultsById);
	}

	public function getResults(int $intCount = PHP_INT_MAX, int $intOffset = 0): array
	{
		$arrResults = \array_slice($this->arrResultsById, $intOffset, $intCount, true);

		$strIds = implode(',', array_keys($arrResults));

		$strQuery = 'SELECT * FROM tl_search WHERE ID in (' . $strIds . ')';

		$objResult = Database::getInstance()->prepare($strQuery)->execute();

		while ($arrRow = $objResult->fetchAssoc())
		{
			$arrResults[$arrRow['id']] = array_merge($arrRow, $arrResults[$arrRow['id']]);
		}

		return $this->fixMatchesAndRelevance($arrResults);
	}

	private function fixMatchesAndRelevance(array $arrResults): array
	{
		foreach ($arrResults as $k=>$v)
		{
			if ((float) $v['relevance'] === 0.0)
			{
				$arrResults[$k]['relevance'] = PHP_FLOAT_EPSILON;
			}

			$arrHighlight = array();
			$arrWords = Search::splitIntoWords(Utf8::strtolower($v['text']), $v['language']);

			foreach ($this->arrKeywords as $strKeyword)
			{
				if (\in_array($strKeyword, $arrWords))
				{
					$arrHighlight[] = $strKeyword;
				}
			}

			// Highlight the words which matched the wildcard keywords
			foreach ($this->arrWildcards as $strKeyword)
			{
				if ($matches = preg_grep('/^' . str_replace('%', '.*', $strKeyword) . '$/', $arrWords))
				{
					array_push($arrHighlight, ...$matches);
				}
			}

			// Highlight phrases if all their words have matched
			foreach ($this->arrPhrases as $strPhrase)
			{
				$arrPhrase = Search::splitIntoWords($strPhrase, $v['language']);

				if (!array_diff($arrPhrase, $arrWords))
				{
					$arrHighlight[] = $strPhrase;
				}
			}

			$arrResults[$k]['matches'] = implode(',', $arrHighlight);
		}

		return array_values($arrResults);
	}
}
