<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
	 *
	 * @deprecated Deprecated since Contao 5.3, to be removed in Contao 6;
	 *             use the content URL generator instead.
	 */
	public static function generateNewsUrl($objItem, $blnAddArchive=false, $blnAbsolute=false)
	{
		trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s()" is deprecated and will no longer work in Contao 6. Use the content URL generator instead.', __METHOD__);

		try
		{
			$parameters = array();

			// Add the current archive parameter (news archive)
			if ($blnAddArchive && Input::get('month'))
			{
				$parameters['month'] = Input::get('month');
			}

			$url = System::getContainer()->get('contao.routing.content_url_generator')->generate($objItem, $parameters, $blnAbsolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH);
		}
		catch (ExceptionInterface)
		{
			return Environment::get('requestUri');
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
		$urlGenerator = System::getContainer()->get('contao.routing.content_url_generator');

		$jsonLd = array(
			'@type' => 'NewsArticle',
			'identifier' => '#/schema/news/' . $objArticle->id,
			'headline' => $htmlDecoder->inputEncodedToPlainText($objArticle->headline),
			'datePublished' => date('Y-m-d\TH:i:sP', $objArticle->date),
		);

		try
		{
			$jsonLd['url'] = $urlGenerator->generate($objArticle);
		}
		catch (ExceptionInterface)
		{
			// noop
		}

		if ($objArticle->teaser)
		{
			$jsonLd['description'] = $htmlDecoder->htmlToPlainText($objArticle->teaser);
		}

		if ($objAuthor = UserModel::findById($objArticle->author))
		{
			$jsonLd['author'] = array(
				'@type' => 'Person',
				'name' => $objAuthor->name,
			);
		}

		return $jsonLd;
	}
}
