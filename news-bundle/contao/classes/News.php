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
 * Provide methods regarding news archives.
 */
class News extends Frontend
{
	/**
	 * URL cache array
	 * @var array
	 */
	private static $arrUrlCache = array();

	/**
	 * Generate a URL and return it as string
	 *
	 * @param NewsModel $objItem
	 * @param boolean   $blnAddArchive
	 * @param boolean   $blnAbsolute
	 *
	 * @return string
	 */
	public static function generateNewsUrl($objItem, $blnAddArchive=false, $blnAbsolute=false)
	{
		$strCacheKey = 'id_' . $objItem->id . ($blnAbsolute ? '_absolute' : '') . (($blnAddArchive && Input::get('month')) ? '_' . Input::get('month') : '');

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
				if (str_starts_with($objItem->url, 'mailto:'))
				{
					self::$arrUrlCache[$strCacheKey] = StringUtil::encodeEmail($objItem->url);
				}
				else
				{
					$url = $objItem->url;

					if (Validator::isRelativeUrl($url))
					{
						$url = Environment::get('path') . '/' . $url;
					}

					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($url);
				}
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objTarget->getAbsoluteUrl() : $objTarget->getFrontendUrl());
				}
				break;

			// Link to an article
			case 'article':
				if (($objArticle = ArticleModel::findByPk($objItem->articleId)) instanceof ArticleModel && ($objPid = $objArticle->getRelated('pid')) instanceof PageModel)
				{
					$params = '/articles/' . ($objArticle->alias ?: $objArticle->id);

					/** @var PageModel $objPid */
					self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPid->getAbsoluteUrl($params) : $objPid->getFrontendUrl($params));
				}
				break;
		}

		// Link to the default page
		if (self::$arrUrlCache[$strCacheKey] === null)
		{
			$objPage = PageModel::findByPk($objItem->getRelated('pid')->jumpTo);

			if (!$objPage instanceof PageModel)
			{
				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand(Environment::get('requestUri'));
			}
			else
			{
				$params = '/' . ($objItem->alias ?: $objItem->id);

				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
			}

			// Add the current archive parameter (news archive)
			if ($blnAddArchive && Input::get('month'))
			{
				self::$arrUrlCache[$strCacheKey] .= '?month=' . Input::get('month');
			}
		}

		return self::$arrUrlCache[$strCacheKey];
	}

	/**
	 * Return the schema.org data from a news article
	 *
	 * @param NewsModel $objArticle
	 *
	 * @return array
	 */
	public static function getSchemaOrgData(NewsModel $objArticle): array
	{
		$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

		$jsonLd = array(
			'@type' => 'NewsArticle',
			'identifier' => '#/schema/news/' . $objArticle->id,
			'url' => self::generateNewsUrl($objArticle),
			'headline' => $htmlDecoder->inputEncodedToPlainText($objArticle->headline),
			'datePublished' => date('Y-m-d\TH:i:sP', $objArticle->date),
		);

		if ($objArticle->teaser)
		{
			$jsonLd['description'] = $htmlDecoder->htmlToPlainText($objArticle->teaser);
		}

		/** @var UserModel $objAuthor */
		if (($objAuthor = $objArticle->getRelated('author')) instanceof UserModel)
		{
			$jsonLd['author'] = array(
				'@type' => 'Person',
				'name' => $objAuthor->name,
			);
		}

		return $jsonLd;
	}
}
