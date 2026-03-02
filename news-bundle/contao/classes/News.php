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

/**
 * Provide methods regarding news archives.
 */
class News extends Frontend
{
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
