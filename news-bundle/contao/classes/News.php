<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Util\UrlUtil;
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * Provide methods regarding news archives.
 */
class News extends Frontend
{
	/**
	 * Generate a URL and return it as string
	 *
	 * @param NewsModel $objItem
	 * @param boolean   $blnAddArchive
	 * @param boolean   $blnAbsolute
	 *
	 * @return string
	 */
	public static function generateNewsUrl($objItem, $blnAddArchive=false, $blnAbsolute=true)
	{
		trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s" is deprecated, use the content URL generator instead.', __METHOD__);

		try
		{
			$parameters = array();

			// Add the current archive parameter (news archive)
			if ($blnAddArchive && Input::get('month'))
			{
				$parameters['month'] = Input::get('month');
			}

			$url = System::getContainer()->get('contao.routing.content_url_generator')->generate($objItem, $parameters);
		}
		catch (ExceptionInterface)
		{
			return StringUtil::ampersand(Environment::get('requestUri'));
		}

		if (!$blnAbsolute)
		{
			$url = UrlUtil::makeAbsolutePath($url, Environment::get('base'));
		}

		return $url;
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
