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
 * Stores result from the Search::query() method
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
	private $arrKeywords;

	/**
	 * @var array
	 */
	private $arrWildcards;

	/**
	 * @var array
	 */
	private $arrPhrases;

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

		if (!$arrResults)
		{
			return array();
		}

		$strIds = implode(',', array_keys($arrResults));
		$strQuery = 'SELECT * FROM tl_search WHERE ID in (' . $strIds . ')';
		$objResult = Database::getInstance()->prepare($strQuery)->execute();

		while ($arrRow = $objResult->fetchAssoc())
		{
			$arrRow['relevance'] = (float) $arrResults[$arrRow['id']]['relevance'] > 0
				? $arrResults[$arrRow['id']]['relevance']
				: PHP_FLOAT_EPSILON;

			$arrResults[$arrRow['id']] = $arrRow;
		}

		return array_values($this->addMatches($arrResults));
	}

	private function addMatches(array $arrResults): array
	{
		foreach ($arrResults as $k=>$v)
		{
			$arrHighlight = array();
			$arrWords = Search::splitIntoWords($v['text'], $v['language']);

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

			$arrHighlight = Search::getMatchVariants($arrHighlight, $v['text'], $v['language']);

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

		return $arrResults;
	}
}
