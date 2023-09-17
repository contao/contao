<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\Model\Collection;

/**
 * Provide methods regarding FAQs.
 */
class ModuleFaq extends Frontend
{
	/**
	 * Return the schema.org data from a set of FAQs
	 *
	 * @param Collection|FaqModel[] $arrFaqs
	 *
	 * @return array
	 */
	public static function getSchemaOrgData(iterable $arrFaqs, string $identifier = null): array
	{
		$jsonLd = array(
			'@type' => 'FAQPage',
			'mainEntity' => array(),
		);

		if ($identifier)
		{
			$jsonLd['identifier'] = $identifier;
		}

		$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

		foreach ($arrFaqs as $objFaq)
		{
			$jsonLd['mainEntity'][] = array(
				'@type' => 'Question',
				'name' => $htmlDecoder->inputEncodedToPlainText($objFaq->question),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text' => $htmlDecoder->htmlToPlainText(StringUtil::encodeEmail($objFaq->answer))
				)
			);
		}

		return $jsonLd;
	}
}
