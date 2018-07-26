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
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['newsreader'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set the item from the auto_item parameter
		if (!isset($_GET['items']) && \Config::get('useAutoItem') && isset($_GET['auto_item']))
		{
			\Input::setGet('items', \Input::get('auto_item'));
		}

		// Return an empty string if "items" is not set (to combine list and reader on same page)
		if (!\Input::get('items'))
		{
			return '';
		}

		$this->news_archives = $this->sortOutProtected(\StringUtil::deserialize($this->news_archives));

		if (empty($this->news_archives) || !\is_array($this->news_archives))
		{
			throw new InternalServerErrorException('The news reader ID ' . $this->id . ' has no archives specified.', $this->id);
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
		$objArticle = \NewsModel::findPublishedByParentAndIdOrAlias(\Input::get('items'), $this->news_archives);

		// The news item does not exist or has an external target (see #33)
		if (null === $objArticle || $objArticle->source != 'default')
		{
			throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));
		}

		$arrArticle = $this->parseArticle($objArticle);
		$this->Template->articles = $arrArticle;

		// Overwrite the page title (see #2853 and #4955)
		if ($objArticle->headline != '')
		{
			$objPage->pageTitle = strip_tags(\StringUtil::stripInsertTags($objArticle->headline));
		}

		// Overwrite the page description
		if ($objArticle->teaser != '')
		{
			$objPage->description = $this->prepareMetaDescription($objArticle->teaser);
		}

		$bundles = \System::getContainer()->getParameter('kernel.bundles');

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

		$this->import('Comments');
		$arrNotifies = array();

		// Notify the system administrator
		if ($objArchive->notify != 'notify_author')
		{
			$arrNotifies[] = $GLOBALS['TL_ADMIN_EMAIL'];
		}

		// Notify the author
		if ($objArchive->notify != 'notify_admin')
		{
			/** @var UserModel $objAuthor */
			if (($objAuthor = $objArticle->getRelated('author')) instanceof UserModel && $objAuthor->email != '')
			{
				$arrNotifies[] = $objAuthor->email;
			}
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
