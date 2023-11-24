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
 * Front end module "article list".
 */
class ModuleArticleList extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_articlelist';

	/**
	 * Do not display the module if there are no articles
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['articlelist'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$strBuffer = parent::generate();

		return !empty($this->Template->articles) ? $strBuffer : '';
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		if (!$this->inColumn)
		{
			$this->inColumn = 'main';
		}

		$id = $objPage->id;
		$objTarget = null;

		$this->Template->request = Environment::get('requestUri');

		// Show the articles of a different page
		if ($this->defineRoot && ($objTarget = $this->objModel->getRelated('rootPage')) instanceof PageModel)
		{
			$id = $objTarget->id;

			/** @var PageModel $objTarget */
			$this->Template->request = $objTarget->getFrontendUrl();
		}

		// Get published articles
		$objArticles = ArticleModel::findPublishedByPidAndColumn($id, $this->inColumn);

		if ($objArticles === null)
		{
			$this->Template->articles = array();

			return;
		}

		$intCount = 0;
		$articles = array();
		$objHelper = $objTarget ?: $objPage; // PHP 5.6 compatibility (see #939)

		while ($objArticles->next())
		{
			// Skip first article
			if (++$intCount <= $this->skipFirst)
			{
				continue;
			}

			try
			{
				$href = $objHelper->getFrontendUrl('/articles/' . ($objArticles->alias ?: $objArticles->id));
			}
			catch (ExceptionInterface)
			{
				continue;
			}

			$cssID = StringUtil::deserialize($objArticles->cssID, true);

			$articles[] = array
			(
				'link' => $objArticles->title,
				'title' => StringUtil::specialchars($objArticles->title),
				'id' => ($cssID[0] ?? null) ?: 'article-' . $objArticles->id,
				'articleId' => $objArticles->id,
				'href' => $href
			);
		}

		$this->Template->articles = $articles;
	}
}
