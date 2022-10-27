<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Patchwork\Utf8;

/**
 * Front end module "news reader".
 *
 * @property Comments $Comments
 * @property string   $com_template
 * @property array    $news_archives
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleNewsReader extends ModuleNews
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_newsreader';

	/**
	 * Display a wildcard in the back end
	 *
	 * @throws InternalServerErrorException
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['newsreader'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set the item from the auto_item parameter
		if (!isset($_GET['items']) && isset($_GET['auto_item']) && Config::get('useAutoItem'))
		{
			Input::setGet('items', Input::get('auto_item'));
		}

		// Return an empty string if "items" is not set (to combine list and reader on same page)
		if (!Input::get('items'))
		{
			return '';
		}

		$this->news_archives = $this->sortOutProtected(StringUtil::deserialize($this->news_archives));

		if (empty($this->news_archives) || !\is_array($this->news_archives))
		{
			throw new InternalServerErrorException('The news reader ID ' . $this->id . ' has no archives specified.');
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$this->Template->articles = '';
		$this->Template->referer = 'javascript:history.go(-1)';
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

		// Get the news item
		$objArticle = NewsModel::findPublishedByParentAndIdOrAlias(Input::get('items'), $this->news_archives);

		// The news item does not exist (see #33)
		if ($objArticle === null)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Redirect if the news item has a target URL (see #1498)
		switch ($objArticle->source) {
			case 'internal':
				if ($page = PageModel::findPublishedById($objArticle->jumpTo))
				{
					throw new RedirectResponseException($page->getAbsoluteUrl(), 301);
				}

				throw new InternalServerErrorException('Invalid "jumpTo" value or target page not public');

			case 'article':
				if (($article = ArticleModel::findByPk($objArticle->articleId)) && ($page = PageModel::findPublishedById($article->pid)))
				{
					throw new RedirectResponseException($page->getAbsoluteUrl('/articles/' . ($article->alias ?: $article->id)), 301);
				}

				throw new InternalServerErrorException('Invalid "articleId" value or target page not public');

			case 'external':
				if ($objArticle->url)
				{
					throw new RedirectResponseException($objArticle->url, 301);
				}

				throw new InternalServerErrorException('Empty target URL');
		}

		// Set the default template
		if (!$this->news_template)
		{
			$this->news_template = 'news_full';
		}

		$arrArticle = $this->parseArticle($objArticle);
		$this->Template->articles = $arrArticle;

		// Overwrite the page title (see #2853, #4955 and #87)
		if ($objArticle->pageTitle)
		{
			$objPage->pageTitle = $objArticle->pageTitle;
		}
		elseif ($objArticle->headline)
		{
			$objPage->pageTitle = strip_tags(StringUtil::stripInsertTags($objArticle->headline));
		}

		// Overwrite the page description
		if ($objArticle->description)
		{
			$objPage->description = $objArticle->description;
		}
		elseif ($objArticle->teaser)
		{
			$objPage->description = $this->prepareMetaDescription($objArticle->teaser);
		}

		$bundles = System::getContainer()->getParameter('kernel.bundles');

		// HOOK: comments extension required
		if ($objArticle->noComments || !isset($bundles['ContaoCommentsBundle']))
		{
			$this->Template->allowComments = false;

			return;
		}

		/** @var NewsArchiveModel $objArchive */
		$objArchive = $objArticle->getRelated('pid');
		$this->Template->allowComments = $objArchive->allowComments;

		// Comments are not allowed
		if (!$objArchive->allowComments)
		{
			return;
		}

		// Adjust the comments headline level
		$intHl = min((int) str_replace('h', '', $this->hl), 5);
		$this->Template->hlc = 'h' . ($intHl + 1);

		$this->import(Comments::class, 'Comments');
		$arrNotifies = array();

		// Notify the system administrator
		if ($objArchive->notify != 'notify_author' && isset($GLOBALS['TL_ADMIN_EMAIL']))
		{
			$arrNotifies[] = $GLOBALS['TL_ADMIN_EMAIL'];
		}

		/** @var UserModel $objAuthor */
		if ($objArchive->notify != 'notify_admin' && ($objAuthor = $objArticle->getRelated('author')) instanceof UserModel && $objAuthor->email)
		{
			$arrNotifies[] = $objAuthor->email;
		}

		$objConfig = new \stdClass();

		$objConfig->perPage = $objArchive->perPage;
		$objConfig->order = $objArchive->sortOrder;
		$objConfig->template = $this->com_template;
		$objConfig->requireLogin = $objArchive->requireLogin;
		$objConfig->disableCaptcha = $objArchive->disableCaptcha;
		$objConfig->bbcode = $objArchive->bbcode;
		$objConfig->moderate = $objArchive->moderate;

		$this->Comments->addCommentsToTemplate($this->Template, $objConfig, 'tl_news', $objArticle->id, $arrNotifies);
	}
}

class_alias(ModuleNewsReader::class, 'ModuleNewsReader');
