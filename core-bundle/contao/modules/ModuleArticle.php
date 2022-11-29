<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;

/**
 * Provides methods to handle articles.
 *
 * @property integer $tstamp
 * @property string  $title
 * @property string  $alias
 * @property string  $inColumn
 * @property boolean $showTeaser
 * @property boolean $multiMode
 * @property string  $teaser
 * @property string  $teaserCssID
 * @property string  $classes
 * @property boolean $printable
 * @property boolean $published
 * @property integer $start
 * @property integer $stop
 */
class ModuleArticle extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_article';

	/**
	 * No markup
	 * @var boolean
	 */
	protected $blnNoMarkup = false;

	/**
	 * Check whether the article is published
	 *
	 * @param boolean $blnNoMarkup
	 *
	 * @return string
	 */
	public function generate($blnNoMarkup=false)
	{
		if ($this->isHidden())
		{
			return '';
		}

		$this->type = 'article';
		$this->blnNoMarkup = $blnNoMarkup;

		// Tag the article (see #2137)
		if ($this->objModel !== null)
		{
			System::getContainer()->get('contao.cache.entity_tags')->tagWithModelInstance($this->objModel);
		}

		return parent::generate();
	}

	protected function isHidden()
	{
		$isUnpublished = !$this->published || ($this->start && $this->start > time()) || ($this->stop && $this->stop <= time());

		// The article is published, so show it
		if (!$isUnpublished)
		{
			return false;
		}

		$tokenChecker = System::getContainer()->get('contao.security.token_checker');

		// Preview mode is enabled, so show the article
		if ($tokenChecker->isPreviewMode())
		{
			return false;
		}

		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		// We are in the back end, so show the article
		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			return false;
		}

		return true;
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$id = 'article-' . $this->id;

		// Generate the CSS ID if it is not set
		if (empty($this->cssID[0]))
		{
			$this->cssID = array($id, $this->cssID[1] ?? null);
		}

		$this->Template->column = $this->inColumn;
		$this->Template->noMarkup = $this->blnNoMarkup;

		// Add the modification date
		$this->Template->timestamp = $this->tstamp;
		$this->Template->date = Date::parse($objPage->datimFormat ?? Config::get('datimFormat'), $this->tstamp);

		// Show the teaser only
		if ($this->multiMode && $this->showTeaser)
		{
			$this->cssID = array($id, '');
			$arrCss = StringUtil::deserialize($this->teaserCssID);

			// Override the CSS ID and class
			if (\is_array($arrCss) && \count($arrCss) == 2)
			{
				if (!$arrCss[0])
				{
					$arrCss[0] = $id;
				}

				$this->cssID = $arrCss;
			}

			$article = $this->alias ?: $this->id;
			$href = '/articles/' . (($this->inColumn != 'main') ? $this->inColumn . ':' : '') . $article;

			$this->Template->teaserOnly = true;
			$this->Template->headline = $this->headline;
			$this->Template->href = $objPage->getFrontendUrl($href);
			$this->Template->teaser = $this->teaser ?? '';
			$this->Template->readMore = StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $this->headline), true);
			$this->Template->more = $GLOBALS['TL_LANG']['MSC']['more'];

			return;
		}

		// Get section and article alias
		$chunks = explode(':', Input::get('articles') ?? '');
		$strSection = $chunks[0] ?? null;
		$strArticle = $chunks[1] ?? $strSection;

		// Overwrite the page metadata (see #2853, #4955 and #87)
		if (!$this->blnNoMarkup && $strArticle && ($strArticle == $this->id || $strArticle == $this->alias) && $this->title)
		{
			$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

			if ($responseContext && $responseContext->has(HtmlHeadBag::class))
			{
				$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

				/** @var HtmlHeadBag $htmlHeadBag */
				$htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
				$htmlHeadBag->setTitle($htmlDecoder->inputEncodedToPlainText($this->title ?? ''));

				if ($this->teaser)
				{
					$htmlHeadBag->setMetaDescription($htmlDecoder->htmlToPlainText($this->teaser));
				}
			}
		}

		$this->Template->printable = false;
		$this->Template->backlink = false;

		// Back link
		if (!$this->multiMode && $strArticle && ($strArticle == $this->id || $strArticle == $this->alias))
		{
			$this->Template->backlink = $objPage->getFrontendUrl();
			$this->Template->back = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['goBack']);
		}

		$arrElements = array();
		$objCte = ContentModel::findPublishedByPidAndTable($this->id, 'tl_article');

		if ($objCte !== null)
		{
			while ($objCte->next())
			{
				$arrElements[] = $this->getContentElement($objCte->current(), $this->strColumn);
			}
		}

		$this->Template->teaser = $this->teaser;
		$this->Template->elements = $arrElements;

		if ($this->printable)
		{
			$options = StringUtil::deserialize($this->printable);

			if (!empty($options) && \is_array($options))
			{
				// Remove the PDF option if there is no PDF handler (see #417)
				if (empty($GLOBALS['TL_HOOKS']['printArticleAsPdf']) && ($key = array_search('pdf', $options)) !== false)
				{
					unset($options[$key]);
				}

				if (!empty($options))
				{
					$this->Template->printable = true;
					$this->Template->printButton = \in_array('print', $options);
					$this->Template->pdfButton = \in_array('pdf', $options);
					$this->Template->facebookButton = \in_array('facebook', $options);
					$this->Template->twitterButton = \in_array('twitter', $options);
				}
			}
		}

		// Add syndication variables
		if ($this->Template->printable)
		{
			$request = Environment::get('requestUri');

			// URL encoding will be handled by the Symfony router, so do not apply rawurlencode() here anymore
			$this->Template->print = '#';
			$this->Template->encUrl = Environment::get('uri');
			$this->Template->encTitle = $objPage->pageTitle;
			$this->Template->href = $request . ((strpos($request, '?') !== false) ? '&amp;' : '?') . 'pdf=' . $this->id;

			$this->Template->printTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['printPage']);
			$this->Template->pdfTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['printAsPdf']);
			$this->Template->facebookTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['facebookShare']);
			$this->Template->twitterTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['twitterShare']);
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['compileArticle']) && \is_array($GLOBALS['TL_HOOKS']['compileArticle']))
		{
			foreach ($GLOBALS['TL_HOOKS']['compileArticle'] as $callback)
			{
				$this->import($callback[0]);
				$this->{$callback[0]}->{$callback[1]}($this->Template, $this->arrData, $this);
			}
		}
	}

	protected function getResponseCacheTags(): array
	{
		// Do not tag with 'contao.db.tl_module.<id>' when rendering articles (see #2814)
		return array();
	}
}
