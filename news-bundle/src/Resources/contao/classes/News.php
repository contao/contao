<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Provide methods regarding news archives.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class News extends \Frontend
{

	/**
	 * URL cache array
	 * @var array
	 */
	private static $arrUrlCache = array();


	/**
	 * Update a particular RSS feed
	 *
	 * @param integer $intId
	 */
	public function generateFeed($intId)
	{
		$objFeed = \NewsFeedModel::findByPk($intId);

		if ($objFeed === null)
		{
			return;
		}

		$objFeed->feedName = $objFeed->alias ?: 'news' . $objFeed->id;

		// Delete XML file
		if (\Input::get('act') == 'delete')
		{
			$this->import('Files');
			$this->Files->delete($objFeed->feedName . '.xml');
		}

		// Update XML file
		else
		{
			$this->generateFiles($objFeed->row());
			$this->log('Generated news feed "' . $objFeed->feedName . '.xml"', __METHOD__, TL_CRON);
		}
	}


	/**
	 * Delete old files and generate all feeds
	 */
	public function generateFeeds()
	{
		$this->import('Automator');
		$this->Automator->purgeXmlFiles();

		$objFeed = \NewsFeedModel::findAll();

		if ($objFeed !== null)
		{
			while ($objFeed->next())
			{
				$objFeed->feedName = $objFeed->alias ?: 'news' . $objFeed->id;
				$this->generateFiles($objFeed->row());
				$this->log('Generated news feed "' . $objFeed->feedName . '.xml"', __METHOD__, TL_CRON);
			}
		}
	}


	/**
	 * Generate all feeds including a certain archive
	 * #
	 * @param integer $intId
	 */
	public function generateFeedsByArchive($intId)
	{
		$objFeed = \NewsFeedModel::findByArchive($intId);

		if ($objFeed !== null)
		{
			while ($objFeed->next())
			{
				$objFeed->feedName = $objFeed->alias ?: 'news' . $objFeed->id;

				// Update the XML file
				$this->generateFiles($objFeed->row());
				$this->log('Generated news feed "' . $objFeed->feedName . '.xml"', __METHOD__, TL_CRON);
			}
		}
	}


	/**
	 * Generate an XML files and save them to the root directory
	 *
	 * @param array $arrFeed
	 */
	protected function generateFiles($arrFeed)
	{
		$arrArchives = \StringUtil::deserialize($arrFeed['archives']);

		if (!is_array($arrArchives) || empty($arrArchives))
		{
			return;
		}

		$strType = ($arrFeed['format'] == 'atom') ? 'generateAtom' : 'generateRss';
		$strLink = $arrFeed['feedBase'] ?: \Environment::get('base');
		$strFile = $arrFeed['feedName'];

		$objFeed = new \Feed($strFile);
		$objFeed->link = $strLink;
		$objFeed->title = $arrFeed['title'];
		$objFeed->description = $arrFeed['description'];
		$objFeed->language = $arrFeed['language'];
		$objFeed->published = $arrFeed['tstamp'];

		// Get the items
		if ($arrFeed['maxItems'] > 0)
		{
			$objArticle = \NewsModel::findPublishedByPids($arrArchives, null, $arrFeed['maxItems']);
		}
		else
		{
			$objArticle = \NewsModel::findPublishedByPids($arrArchives);
		}

		// Parse the items
		if ($objArticle !== null)
		{
			$arrUrls = array();

			while ($objArticle->next())
			{
				$jumpTo = $objArticle->getRelated('pid')->jumpTo;

				// No jumpTo page set (see #4784)
				if (!$jumpTo)
				{
					continue;
				}

				// Get the jumpTo URL
				if (!isset($arrUrls[$jumpTo]))
				{
					$objParent = \PageModel::findWithDetails($jumpTo);

					// A jumpTo page is set but does no longer exist (see #5781)
					if ($objParent === null)
					{
						$arrUrls[$jumpTo] = false;
					}
					else
					{
						$arrUrls[$jumpTo] = $objParent->getAbsoluteUrl(\Config::get('useAutoItem') ? '/%s' : '/items/%s');
					}
				}

				// Skip the event if it requires a jumpTo URL but there is none
				if ($arrUrls[$jumpTo] === false && $objArticle->source == 'default')
				{
					continue;
				}

				$strUrl = $arrUrls[$jumpTo];
				$objItem = new \FeedItem();

				$objItem->title = $objArticle->headline;
				$objItem->link = $this->getLink($objArticle, $strUrl);
				$objItem->published = $objArticle->date;

				/** @var BackendUser $objAuthor */
				if (($objAuthor = $objArticle->getRelated('author')) !== null)
				{
					$objItem->author = $objAuthor->name;
				}

				// Prepare the description
				if ($arrFeed['source'] == 'source_text')
				{
					$strDescription = '';
					$objElement = \ContentModel::findPublishedByPidAndTable($objArticle->id, 'tl_news');

					if ($objElement !== null)
					{
						// Overwrite the request (see #7756)
						$strRequest = \Environment::get('request');
						\Environment::set('request', $objItem->link);

						while ($objElement->next())
						{
							$strDescription .= $this->getContentElement($objElement->current());
						}

						\Environment::set('request', $strRequest);
					}
				}
				else
				{
					$strDescription = $objArticle->teaser;
				}

				$strDescription = $this->replaceInsertTags($strDescription, false);
				$objItem->description = $this->convertRelativeUrls($strDescription, $strLink);

				// Add the article image as enclosure
				if ($objArticle->addImage)
				{
					$objFile = \FilesModel::findByUuid($objArticle->singleSRC);

					if ($objFile !== null)
					{
						$objItem->addEnclosure($objFile->path, $strLink);
					}
				}

				// Enclosures
				if ($objArticle->addEnclosure)
				{
					$arrEnclosure = \StringUtil::deserialize($objArticle->enclosure, true);

					if (is_array($arrEnclosure))
					{
						$objFile = \FilesModel::findMultipleByUuids($arrEnclosure);

						if ($objFile !== null)
						{
							while ($objFile->next())
							{
								$objItem->addEnclosure($objFile->path, $strLink);
							}
						}
					}
				}

				$objFeed->addItem($objItem);
			}
		}

		// Create the file
		\File::putContent('web/share/' . $strFile . '.xml', $this->replaceInsertTags($objFeed->$strType(), false));
	}


	/**
	 * Add news items to the indexer
	 *
	 * @param array   $arrPages
	 * @param integer $intRoot
	 * @param boolean $blnIsSitemap
	 *
	 * @return array
	 */
	public function getSearchablePages($arrPages, $intRoot=0, $blnIsSitemap=false)
	{
		$arrRoot = array();

		if ($intRoot > 0)
		{
			$arrRoot = $this->Database->getChildRecords($intRoot, 'tl_page');
		}

		$arrProcessed = array();
		$time = \Date::floorToMinute();

		// Get all news archives
		$objArchive = \NewsArchiveModel::findByProtected('');

		// Walk through each archive
		if ($objArchive !== null)
		{
			while ($objArchive->next())
			{
				// Skip news archives without target page
				if (!$objArchive->jumpTo)
				{
					continue;
				}

				// Skip news archives outside the root nodes
				if (!empty($arrRoot) && !in_array($objArchive->jumpTo, $arrRoot))
				{
					continue;
				}

				// Get the URL of the jumpTo page
				if (!isset($arrProcessed[$objArchive->jumpTo]))
				{
					$objParent = \PageModel::findWithDetails($objArchive->jumpTo);

					// The target page does not exist
					if ($objParent === null)
					{
						continue;
					}

					// The target page has not been published (see #5520)
					if (!$objParent->published || ($objParent->start != '' && $objParent->start > $time) || ($objParent->stop != '' && $objParent->stop <= ($time + 60)))
					{
						continue;
					}

					if ($blnIsSitemap)
					{
						// The target page is protected (see #8416)
						if ($objParent->protected)
						{
							continue;
						}

						// The target page is exempt from the sitemap (see #6418)
						if ($objParent->sitemap == 'map_never')
						{
							continue;
						}
					}

					// Generate the URL
					$arrProcessed[$objArchive->jumpTo] = $objParent->getAbsoluteUrl(\Config::get('useAutoItem') ? '/%s' : '/items/%s');
				}

				$strUrl = $arrProcessed[$objArchive->jumpTo];

				// Get the items
				$objArticle = \NewsModel::findPublishedDefaultByPid($objArchive->id);

				if ($objArticle !== null)
				{
					while ($objArticle->next())
					{
						$arrPages[] = $this->getLink($objArticle, $strUrl);
					}
				}
			}
		}

		return $arrPages;
	}


	/**
	 * Generate a URL and return it as string
	 *
	 * @param NewsModel $objItem
	 * @param boolean   $blnAddArchive
	 *
	 * @return string
	 */
	public static function generateNewsUrl($objItem, $blnAddArchive=false)
	{
		$strCacheKey = 'id_' . $objItem->id;

		// Load the URL from cache
		if (isset(self::$arrUrlCache[$strCacheKey]))
		{
			return self::$arrUrlCache[$strCacheKey];
		}

		// Initialize the cache
		self::$arrUrlCache[$strCacheKey] = null;

		switch ($objItem->source)
		{
			// Link to an external page
			case 'external':
				if (substr($objItem->url, 0, 7) == 'mailto:')
				{
					self::$arrUrlCache[$strCacheKey] = \StringUtil::encodeEmail($objItem->url);
				}
				else
				{
					self::$arrUrlCache[$strCacheKey] = ampersand($objItem->url);
				}
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					self::$arrUrlCache[$strCacheKey] = ampersand($objTarget->getFrontendUrl());
				}
				break;

			// Link to an article
			case 'article':
				if (($objArticle = \ArticleModel::findByPk($objItem->articleId, array('eager'=>true))) !== null && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
				{
					/** @var PageModel $objPid */
					self::$arrUrlCache[$strCacheKey] = ampersand($objPid->getFrontendUrl('/articles/' . ($objArticle->alias ?: $objArticle->id)));
				}
				break;
		}

		// Link to the default page
		if (self::$arrUrlCache[$strCacheKey] === null)
		{
			$objPage = \PageModel::findByPk($objItem->getRelated('pid')->jumpTo);

			if (!$objPage instanceof PageModel)
			{
				self::$arrUrlCache[$strCacheKey] = ampersand(\Environment::get('request'), true);
			}
			else
			{
				self::$arrUrlCache[$strCacheKey] = ampersand($objPage->getFrontendUrl((\Config::get('useAutoItem') ? '/' : '/items/') . ($objItem->alias ?: $objItem->id)));
			}

			// Add the current archive parameter (news archive)
			if ($blnAddArchive && \Input::get('month') != '')
			{
				self::$arrUrlCache[$strCacheKey] .= '?month=' . \Input::get('month');
			}
		}

		return self::$arrUrlCache[$strCacheKey];
	}


	/**
	 * Return the link of a news article
	 *
	 * @param NewsModel $objItem
	 * @param string    $strUrl
	 * @param string    $strBase
	 *
	 * @return string
	 */
	protected function getLink($objItem, $strUrl, $strBase='')
	{
		switch ($objItem->source)
		{
			// Link to an external page
			case 'external':
				return $objItem->url;
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					return $objTarget->getAbsoluteUrl();
				}
				break;

			// Link to an article
			case 'article':
				if (($objArticle = \ArticleModel::findByPk($objItem->articleId, array('eager'=>true))) !== null && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
				{
					/** @var PageModel $objPid */
					return ampersand($objPid->getAbsoluteUrl('/articles/' . ($objArticle->alias ?: $objArticle->id)));
				}
				break;
		}

		// Backwards compatibility (see #8329)
		if ($strBase != '' && !preg_match('#^https?://#', $strUrl))
		{
			$strUrl = $strBase . $strUrl;
		}

		// Link to the default page
		return sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), ($objItem->alias ?: $objItem->id));
	}


	/**
	 * Return the names of the existing feeds so they are not removed
	 *
	 * @return array
	 */
	public function purgeOldFeeds()
	{
		$arrFeeds = array();
		$objFeeds = \NewsFeedModel::findAll();

		if ($objFeeds !== null)
		{
			while ($objFeeds->next())
			{
				$arrFeeds[] = $objFeeds->alias ?: 'news' . $objFeeds->id;
			}
		}

		return $arrFeeds;
	}
}
