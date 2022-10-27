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
use Doctrine\DBAL\Driver\Connection;
use Nyholm\Psr7\Uri;

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
		$arrSet['title'] = $arrData['title'];
		$arrSet['protected'] = $arrData['protected'];
		$arrSet['filesize'] = $arrData['filesize'] ?? null;
		$arrSet['groups'] = $arrData['groups'];
		$arrSet['pid'] = $arrData['pid'];
		$arrSet['language'] = $arrData['language'];

		// Ensure that the URL only contains ASCII characters (see #4260)
		$arrSet['url'] = (string) (new Uri($arrData['url']));

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

		if ($objIndex->numRows)
		{
			// The new URL is more canonical (shorter and/or less fragments)
			if (self::compareUrls($arrSet['url'], $objIndex->url) < 0)
			{
				self::removeEntry($arrSet['url']);

				$objDatabase->prepare("UPDATE tl_search %s WHERE id=?")
							->set($arrSet)
							->execute($objIndex->id);
			}

			// The same page has been indexed under a different URL already (see #8460)
			return false;
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
		$arrWords = self::splitIntoWords($strText, $arrData['language']);
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

		// As the search index is shared across all languages, we can not use
		// locale specific rules here (like de-ASCII or tr-Lower).
		if (\in_array('Latin-ASCII', \Transliterator::listIDs(), true))
		{
			$transliterator = \Transliterator::createFromRules('::Latin-ASCII; ::Lower;');
		}
		else
		{
			$transliterator = \Transliterator::create('Lower');
		}

		$words = array();

		foreach ($iterator->getPartsIterator() as $part)
		{
			if ($iterator->getRuleStatus() !== \IntlBreakIterator::WORD_NONE)
			{
				// Limit length to 64 to fit in the database
				$words[] = mb_substr($transliterator->transliterate($part), 0, 64, 'UTF-8');
			}
		}

		return $words;
	}

	/**
	 * Get different variants of the matched words that are present in the text,
	 * e.g. with accents or diaeresis.
	 *
	 * @return string[]
	 */
	public static function getMatchVariants(array $arrMatches, string $strText, string $strLocale): array
	{
		$iterator = \IntlRuleBasedBreakIterator::createWordInstance($strLocale);
		$iterator->setText($strText);

		// As the search index is shared across all languages, we can not use
		// locale specific rules here (like de-ASCII or tr-Lower).
		if (\in_array('Latin-ASCII', \Transliterator::listIDs(), true))
		{
			$transliterator = \Transliterator::createFromRules('::Latin-ASCII; ::Lower;');
		}
		else
		{
			$transliterator = \Transliterator::create('Lower');
		}

		$arrMatches = array_map(
			static function ($match) use ($transliterator)
			{
				return $transliterator->transliterate($match);
			},
			$arrMatches
		);

		$variants = array();

		foreach ($iterator->getPartsIterator() as $part)
		{
			if ($iterator->getRuleStatus() !== \IntlBreakIterator::WORD_NONE && !\in_array($part, $variants, true) && \in_array($transliterator->transliterate($part), $arrMatches, true))
			{
				$variants[] = $part;
			}
		}

		return $variants;
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

		// Check keyword string
		if (!\strlen($strKeywords))
		{
			throw new \Exception('Empty keyword string');
		}

		// Split keywords
		$arrChunks = array();
		preg_match_all('/"[^"]+"|\S+/', $strKeywords, $arrChunks);

		$arrPhrases = array();
		$arrKeywords = array();
		$arrWildcards = array();
		$arrIncluded = array();
		$arrExcluded = array();

		foreach (array_unique($arrChunks[0]) as $strKeyword)
		{
			if (($strKeyword[0] === '*' || substr($strKeyword, -1) === '*') && \strlen($strKeyword) > 1)
			{
				$arrWildcardWords = self::splitIntoWords(trim($strKeyword, '*'), $GLOBALS['TL_LANGUAGE']);

				foreach ($arrWildcardWords as $intIndex => $strWord)
				{
					if ($intIndex === 0 && $strKeyword[0] === '*')
					{
						$strWord = '%' . $strWord;
					}

					if ($intIndex === \count($arrWildcardWords) - 1 && substr($strKeyword, -1) === '*')
					{
						$strWord .= '%';
					}

					if ($strWord[0] === '%' || substr($strWord, -1) === '%')
					{
						$arrWildcards[] = $strWord;
					}
					else
					{
						$arrKeywords[] = $strWord;
					}
				}

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

		// Count keywords
		$intPhrases = \count($arrPhrases);
		$intWildcards = \count($arrWildcards);
		$intIncluded = \count($arrIncluded);
		$intExcluded = \count($arrExcluded);

		$intKeywords = 0;
		$arrValues = array();

		// Remember found words so we can highlight them later
		$strQuery = "SELECT * FROM (SELECT tl_search_index.pid AS sid, GROUP_CONCAT(tl_search_index.word) AS matches";

		// Get the number of wildcard matches if wildcards and keywords are mixed
		if (!$blnOrSearch && $intWildcards && (\count($arrKeywords) || $intIncluded || $intPhrases))
		{
			$strQuery .= ", (SELECT COUNT(*) FROM tl_search_index WHERE (" . implode(' OR ', array_fill(0, $intWildcards, 'word LIKE ?')) . ") AND pid=sid) AS wildcards";
			$arrValues = array_merge($arrValues, $arrWildcards);
		}

		// Count the number of matches
		$strQuery .= ", COUNT(*) AS count";

		// Get the relevance
		$strQuery .= ", SUM(relevance) AS relevance";

		// Prepare keywords array
		$arrAllKeywords = array();

		// Get keywords
		if (!empty($arrKeywords))
		{
			$arrAllKeywords[] = implode(' OR ', array_fill(0, \count($arrKeywords), 'word=?'));
			$arrValues = array_merge($arrValues, $arrKeywords);
			$intKeywords += \count($arrKeywords);
		}

		// Get included keywords
		if ($intIncluded)
		{
			$arrAllKeywords[] = implode(' OR ', array_fill(0, $intIncluded, 'word=?'));
			$arrValues = array_merge($arrValues, $arrIncluded);
			$intKeywords += $intIncluded;
		}

		// Get keywords from phrases
		if ($intPhrases)
		{
			foreach ($arrPhrases as $strPhrase)
			{
				$arrWords = self::splitIntoWords(str_replace('[^[:alnum:]]+', ' ', $strPhrase), $GLOBALS['TL_LANGUAGE']);
				$arrAllKeywords[] = implode(' OR ', array_fill(0, \count($arrWords), 'word=?'));
				$arrValues = array_merge($arrValues, $arrWords);
				$intKeywords += \count($arrWords);
			}
		}

		// Get wildcards
		if ($intWildcards)
		{
			$arrAllKeywords[] = implode(' OR ', array_fill(0, $intWildcards, 'word LIKE ?'));
			$arrValues = array_merge($arrValues, $arrWildcards);
		}

		$strQuery .= " FROM (SELECT word FROM tl_search_index WHERE (" . (implode(' OR ', $arrAllKeywords) ?: "0") . ") GROUP BY word) words JOIN tl_search_index ON tl_search_index.word = words.word WHERE 1";

		// Get phrases
		if ($intPhrases)
		{
			$strQuery .= " AND (" . implode(($blnOrSearch ? ' OR ' : ' AND '), array_fill(0, $intPhrases, 'tl_search_index.pid IN(SELECT id FROM tl_search WHERE text REGEXP ?)')) . ")";
			$arrValues = array_merge($arrValues, $arrPhrases);
		}

		// Include keywords
		if ($intIncluded)
		{
			$strQuery .= " AND tl_search_index.pid IN(SELECT pid FROM tl_search_index WHERE " . implode(' OR ', array_fill(0, $intIncluded, 'word=?')) . ")";
			$arrValues = array_merge($arrValues, $arrIncluded);
		}

		// Exclude keywords
		if ($intExcluded)
		{
			$strQuery .= " AND tl_search_index.pid NOT IN(SELECT pid FROM tl_search_index WHERE " . implode(' OR ', array_fill(0, $intExcluded, 'word=?')) . ")";
			$arrValues = array_merge($arrValues, $arrExcluded);
		}

		// Limit results to a particular set of pages
		if (!empty($arrPid) && \is_array($arrPid))
		{
			$strQuery .= " AND tl_search_index.pid IN(SELECT id FROM tl_search WHERE pid IN(" . implode(',', array_map('\intval', $arrPid)) . "))";
		}

		$strQuery .= " GROUP BY tl_search_index.pid";

		// Sort by relevance
		$strQuery .= " ORDER BY relevance DESC) matches LEFT JOIN tl_search ON(matches.sid=tl_search.id)";

		// Make sure to find all words
		if (!$blnOrSearch)
		{
			// Number of keywords without wildcards
			$strQuery .= " WHERE matches.count >= " . $intKeywords;

			// Dynamically add the number of wildcard matches
			if ($intWildcards)
			{
				if ($intKeywords)
				{
					$strQuery .= " + IF(matches.wildcards>" . $intWildcards . ", matches.wildcards, " . $intWildcards . ")";
				}
				else
				{
					$strQuery .= " + " . $intWildcards;
				}
			}
		}

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
	public static function removeEntry($strUrl, Connection $connection = null)
	{
		/** @var Connection $connection */
		$connection = $connection ?? System::getContainer()->get('database_connection');

		$result = $connection->prepare('SELECT id FROM tl_search WHERE url=:url')
			->executeQuery(array('url' => $strUrl))
		;

		foreach ($result->fetchAllAssociativeIndexed() as $id)
		{
			$connection->delete('tl_search', array('id' => $id));
			$connection->delete('tl_search_index', array('pid' => $id));
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

	/**
	 * @param string $strUrlA
	 * @param string $strUrlB
	 *
	 * @return int negative if $strUrlA is more canonical, positive if $strUrlB is more canonical
	 */
	private static function compareUrls($strUrlA, $strUrlB)
	{
		if (strpos($strUrlA, '?') === false && strpos($strUrlB, '?') !== false)
		{
			return -1;
		}

		if (strpos($strUrlA, '?') !== false && strpos($strUrlB, '?') === false)
		{
			return 1;
		}

		$slashCountA = substr_count(explode('?', $strUrlA)[0], '/');
		$slashCountB = substr_count(explode('?', $strUrlB)[0], '/');

		if ($slashCountA !== $slashCountB)
		{
			return $slashCountA - $slashCountB;
		}

		if (\strlen($strUrlA) !== \strlen($strUrlB))
		{
			return \strlen($strUrlA) - \strlen($strUrlB);
		}

		return strcmp($strUrlA, $strUrlB);
	}
}

class_alias(Search::class, 'Search');
