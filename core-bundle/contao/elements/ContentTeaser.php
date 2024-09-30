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
 * Front end content element "teaser".
 */
class ContentTeaser extends ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_teaser';

	/**
	 * Article object
	 * @var ArticleModel
	 */
	protected $objArticle;

	/**
	 * Parent page object
	 * @var PageModel
	 */
	protected $objParent;

	/**
	 * Check whether the target page and the article are published
	 *
	 * @return string
	 */
	public function generate()
	{
		$objArticle = ArticleModel::findPublishedById($this->article);

		if ($objArticle === null)
		{
			return '';
		}

		// Use findPublished() instead of getRelated()
		$objParent = PageModel::findPublishedById($objArticle->pid);

		if ($objParent === null)
		{
			return '';
		}

		$this->objArticle = $objArticle;
		$this->objParent = $objParent;

		return parent::generate();
	}

	/**
	 * Generate the content element
	 */
	protected function compile()
	{
		$objArticle = $this->objArticle;
		$urlGenerator = System::getContainer()->get('contao.routing.content_url_generator');

		$this->Template->href = $urlGenerator->generate($objArticle);
		$this->Template->text = $objArticle->teaser;
		$this->Template->headline = $objArticle->title;
		$this->Template->readMore = StringUtil::specialchars(\sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objArticle->title));
		$this->Template->more = $GLOBALS['TL_LANG']['MSC']['more'];
	}
}
