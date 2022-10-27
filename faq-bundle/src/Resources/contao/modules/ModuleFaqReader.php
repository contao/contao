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
 * Class ModuleFaqReader
 *
 * @property Comments $Comments
 * @property string   $com_template
 * @property array    $faq_categories
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleFaqReader extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_faqreader';

	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['faqreader'][0]) . ' ###';
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

		$this->faq_categories = StringUtil::deserialize($this->faq_categories);

		if (empty($this->faq_categories) || !\is_array($this->faq_categories))
		{
			throw new InternalServerErrorException('The FAQ reader ID ' . $this->id . ' has no categories specified.');
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

		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
		$this->Template->referer = 'javascript:history.go(-1)';

		$objFaq = FaqModel::findPublishedByParentAndIdOrAlias(Input::get('items'), $this->faq_categories);

		if (null === $objFaq)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Add the FAQ record to the template (see #221)
		$this->Template->faq = $objFaq->row();

		// Overwrite the page title and description (see #2853 and #4955)
		if ($objFaq->question)
		{
			$objPage->pageTitle = strip_tags(StringUtil::stripInsertTags($objFaq->question));
			$objPage->description = $this->prepareMetaDescription($objFaq->question);
		}

		$this->Template->question = $objFaq->question;

		// Clean the RTE output
		$objFaq->answer = StringUtil::toHtml5($objFaq->answer);

		$this->Template->answer = StringUtil::encodeEmail($objFaq->answer);
		$this->Template->addImage = false;

		// Add image
		if ($objFaq->addImage && $objFaq->singleSRC)
		{
			$objModel = FilesModel::findByUuid($objFaq->singleSRC);

			if ($objModel !== null && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objModel->path))
			{
				// Do not override the field now that we have a model registry (see #6303)
				$arrFaq = $objFaq->row();
				$arrFaq['singleSRC'] = $objModel->path;

				$this->addImageToTemplate($this->Template, $arrFaq, null, null, $objModel);
			}
		}

		$this->Template->enclosure = array();

		// Add enclosure
		if ($objFaq->addEnclosure)
		{
			$this->addEnclosuresToTemplate($this->Template, $objFaq->row());
		}

		$strAuthor = '';

		/** @var UserModel $objAuthor */
		if (($objAuthor = $objFaq->getRelated('author')) instanceof UserModel)
		{
			$strAuthor = $objAuthor->name;
		}

		$this->Template->info = sprintf($GLOBALS['TL_LANG']['MSC']['faqCreatedBy'], Date::parse($objPage->dateFormat, $objFaq->tstamp), $strAuthor);

		// Tag the FAQ (see #2137)
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags(array('contao.db.tl_faq.' . $objFaq->id));
		}

		$bundles = System::getContainer()->getParameter('kernel.bundles');

		// HOOK: comments extension required
		if ($objFaq->noComments || !isset($bundles['ContaoCommentsBundle']))
		{
			$this->Template->allowComments = false;

			return;
		}

		/** @var FaqCategoryModel $objCategory */
		$objCategory = $objFaq->getRelated('pid');
		$this->Template->allowComments = $objCategory->allowComments;

		// Comments are not allowed
		if (!$objCategory->allowComments)
		{
			return;
		}

		// Adjust the comments headline level
		$intHl = min((int) str_replace('h', '', $this->hl), 5);
		$this->Template->hlc = 'h' . ($intHl + 1);

		$this->import(Comments::class, 'Comments');
		$arrNotifies = array();

		// Notify the system administrator
		if ($objCategory->notify != 'notify_author' && isset($GLOBALS['TL_ADMIN_EMAIL']))
		{
			$arrNotifies[] = $GLOBALS['TL_ADMIN_EMAIL'];
		}

		/** @var UserModel $objAuthor */
		if ($objCategory->notify != 'notify_admin' && ($objAuthor = $objFaq->getRelated('author')) instanceof UserModel && $objAuthor->email)
		{
			$arrNotifies[] = $objAuthor->email;
		}

		$objConfig = new \stdClass();

		$objConfig->perPage = $objCategory->perPage;
		$objConfig->order = $objCategory->sortOrder;
		$objConfig->template = $this->com_template;
		$objConfig->requireLogin = $objCategory->requireLogin;
		$objConfig->disableCaptcha = $objCategory->disableCaptcha;
		$objConfig->bbcode = $objCategory->bbcode;
		$objConfig->moderate = $objCategory->moderate;

		$this->Comments->addCommentsToTemplate($this->Template, $objConfig, 'tl_faq', $objFaq->id, $arrNotifies);
	}
}

class_alias(ModuleFaqReader::class, 'ModuleFaqReader');
