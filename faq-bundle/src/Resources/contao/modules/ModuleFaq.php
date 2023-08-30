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
	 * Add FAQs to the indexer
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
		$time = time();

		// Get all categories
		$objFaq = FaqCategoryModel::findAll();

		// Walk through each category
		if ($objFaq !== null)
		{
			while ($objFaq->next())
			{
				// Skip FAQs without target page
				if (!$objFaq->jumpTo)
				{
					continue;
				}

				// Skip FAQs outside the root nodes
				if (!empty($arrRoot) && !\in_array($objFaq->jumpTo, $arrRoot))
				{
					continue;
				}

				// Get the URL of the jumpTo page
				if (!isset($arrProcessed[$objFaq->jumpTo]))
				{
					$objParent = PageModel::findWithDetails($objFaq->jumpTo);

					// The target page does not exist
					if ($objParent === null)
					{
						continue;
					}

					// The target page has not been published (see #5520)
					if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time))
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
						if ($objParent->robots == 'noindex,nofollow')
						{
							continue;
						}
					}

					// Generate the URL
					$arrProcessed[$objFaq->jumpTo] = $objParent->getAbsoluteUrl(Config::get('useAutoItem') ? '/%s' : '/items/%s');
				}

				$strUrl = $arrProcessed[$objFaq->jumpTo];

				// Get the items
				$objItems = FaqModel::findPublishedByPid($objFaq->id);

				if ($objItems !== null)
				{
					while ($objItems->next())
					{
						if ($blnIsSitemap && $objItems->robots === 'noindex,nofollow')
						{
							continue;
						}

						$arrPages[] = sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), ($objItems->alias ?: $objItems->id));
					}
				}
			}
		}

		return $arrPages;
	}

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

class_alias(ModuleFaq::class, 'ModuleFaq');
