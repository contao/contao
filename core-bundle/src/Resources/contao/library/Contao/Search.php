<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Database\Result;
use Patchwork\Utf8;

/**
 * Creates and queries the search index
 *
 * The class takes the HTML markup of a page, exctracts the content and writes
 * it to the database (search index). It also provides a method to query the
 * seach index, returning the matching entries.
 *
 * Usage:
 *
 *     Search::indexPage($objPage->row());
 *     $result = Search::searchFor('keyword');
 *
 *     while ($result->next())
 *     {
 *         echo $result->url;
 *     }
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Search
{
	/**
	 * Object instance (Singleton)
	 * @var Search
	 */
	protected static $objInstance;

	/**
	 * Index a page
	 *
	 * @param array $arrData The data array
	 *
	 * @return boolean True if a new record was created
	 */
	public static function indexPage($arrData)
	{
		$objDatabase = Database::getInstance();

		$arrSet['tstamp'] = time();
		$arrSet['url'] = $arrData['url'];
		$arrSet['title'] = $arrData['title'];
		$arrSet['protected'] = $arrData['protected'];
		$arrSet['filesize'] = $arrData['filesize'];
		$arrSet['groups'] = $arrData['groups'];
		$arrSet['pid'] = $arrData['pid'];
		$arrSet['language'] = $arrData['language'];

		// Get the file size from the raw content
		if (!$arrSet['filesize'])
		{
			$arrSet['filesize'] = number_format((\strlen($arrData['content']) / 1024), 2, '.', '');
		}

		// Replace special characters
		$strContent = str_replace(array("\n", "\r", "\t", '&#160;', '&nbsp;', '&shy;'), array(' ', ' ', ' ', ' ', ' ', ''), $arrData['content']);

		// Strip script tags
		while (($intStart = strpos($strContent, '<script')) !== false)
		{
			if (($intEnd = strpos($strContent, '</script>', $intStart)) !== false)
			{
				$strContent = substr($strContent, 0, $intStart) . substr($strContent, $intEnd + 9);
			}
			else
			{
				break; // see #5119
			}
		}

		// Strip style tags
		while (($intStart = strpos($strContent, '<style')) !== false)
		{
			if (($intEnd = strpos($strContent, '</style>', $intStart)) !== false)
			{
				$strContent = substr($strContent, 0, $intStart) . substr($strContent, $intEnd + 8);
			}
			else
			{
				break; // see #5119
			}
		}

		// Strip non-indexable areas
		while (($intStart = strpos($strContent, '<!-- indexer::stop -->')) !== false)
		{
			if (($intEnd = strpos($strContent, '<!-- indexer::continue -->', $intStart)) !== false)
			{
				$intCurrent = $intStart;

				// Handle nested tags
				while (($intNested = strpos($strContent, '<!-- indexer::stop -->', $intCurrent + 22)) !== false && $intNested < $intEnd)
				{
					if (($intNewEnd = strpos($strContent, '<!-- indexer::continue -->', $intEnd + 26)) !== false)
					{
						$intEnd = $intNewEnd;
						$intCurrent = $intNested;
					}
					else
					{
						break; // see #5119
					}
				}

				$strContent = substr($strContent, 0, $intStart) . substr($strContent, $intEnd + 26);
			}
			else
			{
				break; // see #5119
			}
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['indexPage']) && \is_array($GLOBALS['TL_HOOKS']['indexPage']))
		{
			foreach ($GLOBALS['TL_HOOKS']['indexPage'] as $callback)
			{
				System::importStatic($callback[0])->{$callback[1]}($strContent, $arrData, $arrSet);
			}
		}

		// Free the memory
		unset($arrData['content']);

		$arrMatches = array();
		preg_match('/<\/head>/', $strContent, $arrMatches, PREG_OFFSET_CAPTURE);
		$intOffset = \strlen($arrMatches[0][0]) + $arrMatches[0][1];

		// Split page in head and body section
		$strHead = substr($strContent, 0, $intOffset);
		$strBody = substr($strContent, $intOffset);

		unset($strContent);

		$tags = array();

		// Get the description
		if (preg_match('/<meta[^>]+name="description"[^>]+content="([^"]*)"[^>]*>/i', $strHead, $tags))
		{
			$arrData['description'] = trim(preg_replace('/ +/', ' ', StringUtil::decodeEntities($tags[1])));
		}

		// Get the keywords
		if (preg_match('/<meta[^>]+name="keywords"[^>]+content="([^"]*)"[^>]*>/i', $strHead, $tags))
		{
			$arrData['keywords'] = trim(preg_replace('/ +/', ' ', StringUtil::decodeEntities($tags[1])));
		}

		// Read the title and alt attributes
		if (preg_match_all('/<* (title|alt)="([^"]*)"[^>]*>/i', $strBody, $tags))
		{
			$arrData['keywords'] .= ' ' . implode(', ', array_unique($tags[2]));
		}

		// Add a whitespace character before line-breaks and between consecutive tags (see #5363)
		$strBody = str_ireplace(array('<br', '><'), array(' <br', '> <'), $strBody);
		$strBody = strip_tags($strBody);

		// Put everything together
		$arrSet['text'] = $arrData['title'] . ' ' . $arrData['description'] . ' ' . $strBody . ' ' . $arrData['keywords'];
		$arrSet['text'] = trim(preg_replace('/ +/', ' ', StringUtil::decodeEntities($arrSet['text'])));

		// Calculate the checksum
		$arrSet['checksum'] = md5($arrSet['text']);

		$objIndex = $objDatabase->prepare("SELECT id, url FROM tl_search WHERE checksum=? AND pid=?")
								->limit(1)
								->execute($arrSet['checksum'], $arrSet['pid']);

		// Update the URL if the new URL is shorter or the current URL is not canonical
		if ($objIndex->numRows && $objIndex->url != $arrSet['url'])
		{
			if (strpos($objIndex->url, '?') !== false && strpos($arrSet['url'], '?') === false)
			{
				// The new URL is more canonical (no query string)
				$objDatabase->prepare("DELETE FROM tl_search WHERE id=?")
							->execute($objIndex->id);

				$objDatabase->prepare("DELETE FROM tl_search_index WHERE pid=?")
							->execute($objIndex->id);
			}
			elseif (substr_count($arrSet['url'], '/') > substr_count($objIndex->url, '/') || (strpos($arrSet['url'], '?') !== false && strpos($objIndex->url, '?') === false) || \strlen($arrSet['url']) > \strlen($objIndex->url))
			{
				// The current URL is more canonical (shorter and/or less fragments)
				$arrSet['url'] = $objIndex->url;
			}
			else
			{
				// The same page has been indexed under a different URL already (see #8460)
				return false;
			}
		}

		$objIndex = $objDatabase->prepare("SELECT id FROM tl_search WHERE url=?")
								->limit(1)
								->execute($arrSet['url']);

		// Add the page to the tl_search table
		if ($objIndex->numRows)
		{
			$objDatabase->prepare("UPDATE tl_search %s WHERE id=?")
						->set($arrSet)
						->execute($objIndex->id);

			$intInsertId = $objIndex->id;
		}
		else
		{
			$objInsertStmt = $objDatabase->prepare("INSERT INTO tl_search %s")
										 ->set($arrSet)
										 ->execute();

			$intInsertId = $objInsertStmt->insertId;
		}

		// Remove quotes
		$strText = str_replace(array('´', '`'), "'", $arrSet['text']);

		unset($arrSet);

		// Split words
		$arrWords = self::splitIntoWords(Utf8::strtolower($strText), $arrData['language']);
		$arrIndex = array();

		// Index words
		foreach ($arrWords as $strWord)
		{
			if (isset($arrIndex[$strWord]))
			{
				$arrIndex[$strWord]++;
				continue;
			}

			$arrIndex[$strWord] = 1;
		}

		// Remove the existing index
		$objDatabase->prepare("DELETE FROM tl_search_index WHERE pid=?")
					->execute($intInsertId);

		$arrQuery = array();
		$arrValues = array();

		foreach ($arrIndex as $k=>$v)
		{
			$arrQuery[] = '(?, ?, ?, ?)';
			$arrValues[] = $intInsertId;
			$arrValues[] = $k;
			$arrValues[] = $v;
			$arrValues[] = $arrData['language'];
		}

		// Create the new index
		$objDatabase->prepare("INSERT INTO tl_search_index (pid, word, relevance, language) VALUES " . implode(', ', $arrQuery))
					->execute($arrValues);

		return true;
	}

	/**
	 * @return string[]
	 */
	private static function splitIntoWords(string $strText, string $strLocale)
	{
		$iterator = \IntlRuleBasedBreakIterator::createWordInstance($strLocale);
		$iterator->setText($strText);

		$words = array();

		foreach ($iterator->getPartsIterator() as $part)
		{
			if ($iterator->getRuleStatus() !== \IntlBreakIterator::WORD_NONE)
			{
				$words[] = $part;
			}
		}

		return $words;
	}

	/**
	 * Search the index and return the result object
	 *
	 * @param string  $strKeywords  The keyword string
	 * @param boolean $blnOrSearch  If true, the result can contain any keyword
	 * @param array   $arrPid       An optional array of page IDs to limit the result to
	 * @param integer $intRows      An optional maximum number of result rows
	 * @param integer $intOffset    An optional result offset
	 * @param boolean $blnFuzzy     If true, the search will be fuzzy
	 * @param integer $intMinlength Ignore keywords deceeding the minimum length
	 *
	 * @return Result The database result object
	 *
	 * @throws \Exception If the cleaned keyword string is empty
	 */
	public static function searchFor($strKeywords, $blnOrSearch=false, $arrPid=array(), $intRows=0, $intOffset=0, $blnFuzzy=false, $intMinlength=0)
	{
		// Clean the keywords
		$strKeywords = StringUtil::decodeEntities($strKeywords);
		$strKeywords = Utf8::strtolower($strKeywords);

		// Check keyword string
		if (!\strlen($strKeywords))
		{
			throw new \Exception('Empty keyword string');
		}

		// Split keywords
		$arrChunks = array();
		preg_match_all('/"[^"]+"|[+-]?[^ ]+\*?/', $strKeywords, $arrChunks);

		$arrPhrases = array();
		$arrKeywords = array();
		$arrWildcards = array();
		$arrIncluded = array();
		$arrExcluded = array();

		foreach (array_unique($arrChunks[0]) as $strKeyword)
		{
			if (substr($strKeyword, -1) == '*' && \strlen($strKeyword) > 1)
			{
				$arrWildcards[] = str_replace('*', '%', $strKeyword);
				continue;
			}

			switch (substr($strKeyword, 0, 1))
			{
				// Phrases
				case '"':
					if ($strKeyword = trim(substr($strKeyword, 1, -1)))
					{
						$arrPhrases[] = str_replace(' ', '[^[:alnum:]]+', preg_quote($strKeyword));
					}
					break;

				// Included keywords
				case '+':
					if ($strKeyword = trim(substr($strKeyword, 1)))
					{
						foreach (self::splitIntoWords($strKeyword, $GLOBALS['TL_LANGUAGE']) as $strWord)
						{
							$arrIncluded[] = $strWord;
						}
					}
					break;

				// Excluded keywords
				case '-':
					if ($strKeyword = trim(substr($strKeyword, 1)))
					{
						foreach (self::splitIntoWords($strKeyword, $GLOBALS['TL_LANGUAGE']) as $strWord)
						{
							$arrExcluded[] = $strWord;
						}
					}
					break;

				// Wildcards
				case '*':
					if (\strlen($strKeyword) > 1)
					{
						$arrWildcards[] = str_replace('*', '%', $strKeyword);
					}
					break;

				// Normal keywords
				default:
					foreach (self::splitIntoWords($strKeyword, $GLOBALS['TL_LANGUAGE']) as $strWord)
					{
						if ($intMinlength > 0 && \strlen($strWord) < $intMinlength)
						{
							continue;
						}

						$arrKeywords[] = $strWord;
					}
					break;
			}
		}

		// Fuzzy search
		if ($blnFuzzy)
		{
			foreach ($arrKeywords as $strKeyword)
			{
				$arrWildcards[] = '%' . $strKeyword . '%';
			}

			$arrKeywords = array();
		}

		$strQuery = "SELECT *, (relevance / vectorLength) AS cosineSimilarity FROM (SELECT tl_search_index.pid AS sid";

		// Remember found words so we can highlight them later
		$strQuery .= ", GROUP_CONCAT(matchedWords.word) AS matches";

		$arrValues = array();
		$arrAllKeywords = array();
		$arrMatches = array();
		$arrRequiredMatches = array();
		$arrExcludedMatches = array();

		// Get wildcards
		foreach ($arrWildcards as $strKeyword)
		{
			$arrMatches[] = \count($arrAllKeywords);
			$arrAllKeywords[] = 'word LIKE ?';
		}

		// Wildcard values are required three times in the query
		for ($i = 0; $i < 3; $i++)
		{
			foreach ($arrWildcards as $strKeyword)
			{
				$arrValues[] = $strKeyword;
			}
		}

		// Get keywords
		foreach ($arrKeywords as $strKeyword)
		{
			$arrMatches[] = \count($arrAllKeywords);
			$arrAllKeywords[] = 'word=?';
			$arrValues[] = $strKeyword;
		}

		// Get included keywords
		foreach ($arrIncluded as $strKeyword)
		{
			$arrRequiredMatches[] = \count($arrAllKeywords);
			$arrAllKeywords[] = 'word=?';
			$arrValues[] = $strKeyword;
		}

		// Get excluded keywords
		foreach ($arrExcluded as $strKeyword)
		{
			$arrExcludedMatches[] = \count($arrAllKeywords);
			$arrAllKeywords[] = 'word=?';
			$arrValues[] = $strKeyword;
		}

		// Get keywords from phrases
		foreach ($arrPhrases as $strPhrase)
		{
			foreach (self::splitIntoWords(str_replace('[^[:alnum:]]+', ' ', $strPhrase), $GLOBALS['TL_LANGUAGE']) as $strKeyword)
			{
				$arrMatches[] = \count($arrAllKeywords);
				$arrAllKeywords[] = 'word=?';
				$arrValues[] = $strKeyword;
			}
		}

		// Get the relevance
		$strQuery .= ", (0";

		foreach ($arrAllKeywords as $index => $strKeywordExpression)
		{
			if (\in_array($index, $arrExcludedMatches, true))
			{
				continue;
			}

			if (isset($arrWildcards[$index]))
			{
				$strQuery .= "+ (
					(1+LOG(SUM(match$index * relevance))) * POW(LOG((
						SELECT COUNT(*) FROM tl_search
					) / (
						SELECT COUNT(DISTINCT pid) 
						FROM tl_search_words 
						JOIN tl_search_index ON tl_search_index.wordId = tl_search_words.id
						WHERE word LIKE ?
					)), 2) / ".(\count($arrAllKeywords) - \count($arrExcludedMatches))."
				)";
			}
			else
			{
				$strQuery .= "+ (
					(1+LOG(SUM(match$index * relevance))) 
					* POW(MIN(match$index * matchedWords.idf), 2) 
					/ ".(\count($arrAllKeywords) - \count($arrExcludedMatches))."
				)";
			}
		}

		$strQuery .= ") / sqrt(0";

		foreach ($arrAllKeywords as $index => $strKeywordExpression)
		{
			if (\in_array($index, $arrExcludedMatches, true))
			{
				continue;
			}

			if (isset($arrWildcards[$index]))
			{
				$strQuery .= " + POW(LOG((
						SELECT COUNT(*) FROM tl_search
					) / (
						SELECT COUNT(DISTINCT pid) 
						FROM tl_search_words 
						JOIN tl_search_index ON tl_search_index.wordId = tl_search_words.id
						WHERE word LIKE ?
					)
				) / ".(\count($arrAllKeywords) - \count($arrExcludedMatches)).", 2)";
			}
			else
			{
				$strQuery .= "+ POW(MIN(match$index * matchedWords.idf) / ".(\count($arrAllKeywords) - \count($arrExcludedMatches)).", 2)";
			}
		}

		$strQuery .= ") AS relevance";

		$strQuery .= " FROM (SELECT id, word";

		// Calculate inverse document frequency of every matching word
		$strQuery .= ", LOG((SELECT COUNT(*) FROM tl_search) / documentFrequency) AS idf";

		// Store the match of every keyword and wildcard in its own column match0, match1, ...
		foreach ($arrAllKeywords as $index => $strKeywordExpression)
		{
			$strQuery .= ", IF($strKeywordExpression, 1, null) AS match$index";
		}

		$strQuery .= " FROM tl_search_words HAVING";

		// Select all words in the sub query that match any of the keywords or wildcards
		$strQuery .= " match" . implode(" = 1 OR match", array_keys($arrAllKeywords)) . " = 1";

		$strQuery .= ") matchedWords JOIN tl_search_index ON tl_search_index.wordId = matchedWords.id";

		$strQuery .= " GROUP BY tl_search_index.pid";
		$arrHaving = array();

		// Check that all required keywords match
		foreach ($blnOrSearch ? $arrRequiredMatches : array_merge($arrMatches, $arrRequiredMatches) as $intMatch)
		{
			$arrHaving[] = "COUNT(matchedWords.match$intMatch) > 0";
		}

		// Check that none of the excluded keywords match
		foreach ($arrExcludedMatches as $intMatch)
		{
			$arrHaving[] = "COUNT(matchedWords.match$intMatch) = 0";
		}

		if (\count($arrHaving))
		{
			$strQuery .= " HAVING " . implode(" AND ", $arrHaving);
		}

		$strQuery .= ") matches LEFT JOIN tl_search ON(matches.sid=tl_search.id) WHERE 1";

		// Get phrases
		if (\count($arrPhrases))
		{
			$strQuery .= " AND (" . implode(($blnOrSearch ? ' OR ' : ' AND '), array_fill(0, \count($arrPhrases), 'tl_search.text REGEXP ?')) . ')';
			$arrValues = array_merge($arrValues, $arrPhrases);
		}

		// Limit results to a particular set of pages
		if (!empty($arrPid) && \is_array($arrPid))
		{
			$strQuery .= " AND tl_search.pid IN(" . implode(',', array_map('\intval', $arrPid)) . ")";
		}

		// Sort by relevance
		$strQuery .= " ORDER BY cosineSimilarity DESC";

		// Return result
		$objResultStmt = Database::getInstance()->prepare($strQuery);

		if ($intRows > 0)
		{
			$objResultStmt->limit($intRows, $intOffset);
		}

		$objResult = $objResultStmt->execute($arrValues);
		$arrResult = $objResult->fetchAllAssoc();

		foreach ($arrResult as $k=>$v)
		{
			$arrHighlight = array();
			$arrMatches = explode(',', $v['matches']);

			foreach ($arrKeywords as $strKeyword)
			{
				if (\in_array($strKeyword, $arrMatches))
				{
					$arrHighlight[] = $strKeyword;
				}
			}

			foreach ($arrIncluded as $strKeyword)
			{
				if (\in_array($strKeyword, $arrMatches))
				{
					$arrHighlight[] = $strKeyword;
				}
			}

			// Highlight the words which matched the wildcard keywords
			foreach ($arrWildcards as $strKeyword)
			{
				if ($matches = preg_grep('/' . str_replace('%', '.*', $strKeyword) . '/', $arrMatches))
				{
					$arrHighlight = array_merge($arrHighlight, $matches);
				}
			}

			// Highlight phrases if all their words have matched
			foreach ($arrPhrases as $strPhrase)
			{
				$strPhrase = str_replace('[^[:alnum:]]+', ' ', $strPhrase);

				if (!array_diff(explode(' ', $strPhrase), $arrMatches))
				{
					$arrHighlight[] = $strPhrase;
				}
			}

			$arrResult[$k]['matches'] = implode(',', $arrHighlight);
		}

		return new Result($arrResult, $objResult->query);
	}

	/**
	 * Remove an entry from the search index
	 *
	 * @param string $strUrl The URL to be removed
	 */
	public static function removeEntry($strUrl)
	{
		$objDatabase = Database::getInstance();

		$objResult = $objDatabase->prepare("SELECT id FROM tl_search WHERE url=?")
								 ->execute($strUrl);

		while ($objResult->next())
		{
			$objDatabase->prepare("DELETE FROM tl_search WHERE id=?")
						->execute($objResult->id);

			$objDatabase->prepare("DELETE FROM tl_search_index WHERE pid=?")
						->execute($objResult->id);
		}
	}

	/**
	 * Prevent cloning of the object (Singleton)
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             The Search class is now static.
	 */
	final public function __clone()
	{
	}

	/**
	 * Return the object instance (Singleton)
	 *
	 * @return Search The object instance
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             The Search class is now static.
	 */
	public static function getInstance()
	{
		@trigger_error('Using Search::getInstance() has been deprecated and will no longer work in Contao 5.0. The Search class is now static.', E_USER_DEPRECATED);

		if (static::$objInstance === null)
		{
			static::$objInstance = new static();
		}

		return static::$objInstance;
	}
}

class_alias(Search::class, 'Search');
