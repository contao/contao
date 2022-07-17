<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Model\Collection;

/**
 * Parent class for news modules.
 *
 * @property string $news_template
 */
abstract class ModuleNews extends Module
{
	/**
	 * Sort out protected archives
	 *
	 * @param array $arrArchives
	 *
	 * @return array
	 */
	protected function sortOutProtected($arrArchives)
	{
		if (empty($arrArchives) || !\is_array($arrArchives))
		{
			return $arrArchives;
		}

		$objArchive = NewsArchiveModel::findMultipleByIds($arrArchives);
		$arrArchives = array();

		if ($objArchive !== null)
		{
			$security = System::getContainer()->get('security.helper');

			while ($objArchive->next())
			{
				if ($objArchive->protected && !$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, StringUtil::deserialize($objArchive->groups, true)))
				{
					continue;
				}

				$arrArchives[] = $objArchive->id;
			}
		}

		return $arrArchives;
	}

	/**
	 * Parse an item and return it as string
	 *
	 * @param NewsModel $objArticle
	 * @param boolean   $blnAddArchive
	 * @param string    $strClass
	 * @param integer   $intCount
	 *
	 * @return string
	 */
	protected function parseArticle($objArticle, $blnAddArchive=false, $strClass='', $intCount=0)
	{
		$objTemplate = new FrontendTemplate($this->news_template ?: 'news_latest');
		$objTemplate->setData($objArticle->row());

		if ($objArticle->cssClass)
		{
			$strClass = ' ' . $objArticle->cssClass . $strClass;
		}

		if ($objArticle->featured)
		{
			$strClass = ' featured' . $strClass;
		}

		$objTemplate->class = $strClass;
		$objTemplate->newsHeadline = $objArticle->headline;
		$objTemplate->subHeadline = $objArticle->subheadline;
		$objTemplate->hasSubHeadline = $objArticle->subheadline ? true : false;
		$objTemplate->linkHeadline = $this->generateLink($objArticle->headline, $objArticle, $blnAddArchive);
		$objTemplate->more = $this->generateLink($objArticle->linkText ?: $GLOBALS['TL_LANG']['MSC']['more'], $objArticle, $blnAddArchive, true);
		$objTemplate->link = News::generateNewsUrl($objArticle, $blnAddArchive);
		$objTemplate->archive = $objArticle->getRelated('pid');
		$objTemplate->count = $intCount; // see #5708
		$objTemplate->text = '';
		$objTemplate->hasTeaser = false;
		$objTemplate->hasReader = true;

		// Clean the RTE output
		if ($objArticle->teaser)
		{
			$objTemplate->hasTeaser = true;
			$objTemplate->teaser = $objArticle->teaser;
			$objTemplate->teaser = StringUtil::encodeEmail($objTemplate->teaser);
		}

		// Display the "read more" button for external/article links
		if ($objArticle->source != 'default')
		{
			$objTemplate->text = true;
			$objTemplate->hasText = true;
			$objTemplate->hasReader = false;
		}

		// Compile the news text
		else
		{
			$id = $objArticle->id;

			$objTemplate->text = function () use ($id)
			{
				$strText = '';
				$objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_news');

				if ($objElement !== null)
				{
					while ($objElement->next())
					{
						$strText .= $this->getContentElement($objElement->current());
					}
				}

				return $strText;
			};

			$objTemplate->hasText = static function () use ($objArticle)
			{
				return ContentModel::countPublishedByPidAndTable($objArticle->id, 'tl_news') > 0;
			};
		}

		/** @var PageModel $objPage */
		global $objPage;

		$objTemplate->date = Date::parse($objPage->datimFormat, $objArticle->date);

		/** @var UserModel $objAuthor */
		if (($objAuthor = $objArticle->getRelated('author')) instanceof UserModel)
		{
			$objTemplate->author = $GLOBALS['TL_LANG']['MSC']['by'] . ' ' . $objAuthor->name;
		}

		if (!$objArticle->noComments && $objArticle->source == 'default' && isset(System::getContainer()->getParameter('kernel.bundles')['ContaoCommentsBundle']))
		{
			$intTotal = CommentsModel::countPublishedBySourceAndParent('tl_news', $objArticle->id);

			$objTemplate->numberOfComments = $intTotal;
			$objTemplate->commentCount = sprintf($GLOBALS['TL_LANG']['MSC']['commentCount'], $intTotal);
		}

		// Add the meta information
		$objTemplate->timestamp = $objArticle->date;
		$objTemplate->datetime = date('Y-m-d\TH:i:sP', $objArticle->date);
		$objTemplate->addImage = false;
		$objTemplate->addBefore = false;

		// Add an image
		if ($objArticle->addImage)
		{
			$imgSize = $objArticle->size ?: null;

			// Override the default image size
			if ($this->imgSize)
			{
				$size = StringUtil::deserialize($this->imgSize);

				if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
				{
					$imgSize = $this->imgSize;
				}
			}

			$figureBuilder = System::getContainer()
				->get('contao.image.studio')
				->createFigureBuilder()
				->from($objArticle->singleSRC)
				->setSize($imgSize)
				->setMetadata($objArticle->getOverwriteMetadata())
				->enableLightbox($objArticle->fullsize);

			// If the external link is opened in a new window, open the image link in a new window as well (see #210)
			if ('external' === $objTemplate->source && $objTemplate->target)
			{
				$figureBuilder->setLinkAttribute('target', '_blank');
			}

			if (null !== ($figure = $figureBuilder->buildIfResourceExists()))
			{
				// Rebuild with link to news article if none is set
				if (!$figure->getLinkHref())
				{
					$linkTitle = StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objArticle->headline), true);

					$figure = $figureBuilder
						->setLinkHref($objTemplate->link)
						->setLinkAttribute('title', $linkTitle)
						->build();
				}

				$figure->applyLegacyTemplateData($objTemplate, null, $objArticle->floating);
			}
		}

		$objTemplate->enclosure = array();

		// Add enclosures
		if ($objArticle->addEnclosure)
		{
			$this->addEnclosuresToTemplate($objTemplate, $objArticle->row());
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['parseArticles']) && \is_array($GLOBALS['TL_HOOKS']['parseArticles']))
		{
			foreach ($GLOBALS['TL_HOOKS']['parseArticles'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($objTemplate, $objArticle->row(), $this);
			}
		}

		// Tag the news (see #2137)
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags(array('contao.db.tl_news.' . $objArticle->id));
		}

		// schema.org information
		$objTemplate->getSchemaOrgData = static function () use ($objTemplate, $objArticle): array
		{
			$jsonLd = News::getSchemaOrgData($objArticle);

			if ($objTemplate->addImage && $objTemplate->figure)
			{
				$jsonLd['image'] = $objTemplate->figure->getSchemaOrgData();
			}

			return $jsonLd;
		};

		return $objTemplate->parse();
	}

	/**
	 * Parse one or more items and return them as array
	 *
	 * @param Collection $objArticles
	 * @param boolean    $blnAddArchive
	 *
	 * @return array
	 */
	protected function parseArticles($objArticles, $blnAddArchive=false)
	{
		$limit = $objArticles->count();

		if ($limit < 1)
		{
			return array();
		}

		$count = 0;
		$arrArticles = array();
		$uuids = array();

		foreach ($objArticles as $objArticle)
		{
			if ($objArticle->addImage && $objArticle->singleSRC)
			{
				$uuids[] = $objArticle->singleSRC;
			}
		}

		// Preload all images in one query, so they are loaded into the model registry
		FilesModel::findMultipleByUuids($uuids);

		foreach ($objArticles as $objArticle)
		{
			$arrArticles[] = $this->parseArticle($objArticle, $blnAddArchive, '', $count);
		}

		return $arrArticles;
	}

	/**
	 * Generate a link and return it as string
	 *
	 * @param string    $strLink
	 * @param NewsModel $objArticle
	 * @param boolean   $blnAddArchive
	 * @param boolean   $blnIsReadMore
	 *
	 * @return string
	 */
	protected function generateLink($strLink, $objArticle, $blnAddArchive=false, $blnIsReadMore=false)
	{
		$blnIsInternal = $objArticle->source != 'external';
		$strReadMore = $blnIsInternal ? $GLOBALS['TL_LANG']['MSC']['readMore'] : $GLOBALS['TL_LANG']['MSC']['open'];
		$strArticleUrl = News::generateNewsUrl($objArticle, $blnAddArchive);

		return sprintf(
			'<a href="%s" title="%s"%s>%s%s</a>',
			$strArticleUrl,
			StringUtil::specialchars(sprintf($strReadMore, $blnIsInternal ? $objArticle->headline : $strArticleUrl), true),
			($objArticle->target && !$blnIsInternal ? ' target="_blank" rel="noreferrer noopener"' : ''),
			$strLink,
			($blnIsReadMore && $blnIsInternal ? '<span class="invisible"> ' . $objArticle->headline . '</span>' : '')
		);
	}
}
