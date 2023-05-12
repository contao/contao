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
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;

/**
 * Class ModuleFaqReader
 *
 * @property Comments $Comments
 * @property string   $com_template
 * @property array    $faq_categories
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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['faqreader'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		// Return an empty string if "auto_item" is not set to combine list and reader on same page
		if (Input::get('auto_item') === null)
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

		if ($this->overviewPage)
		{
			$this->Template->referer = PageModel::findById($this->overviewPage)->getFrontendUrl();
			$this->Template->back = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['faqOverview'];
		}

		$objFaq = FaqModel::findPublishedByParentAndIdOrAlias(Input::get('auto_item'), $this->faq_categories);

		if (null === $objFaq)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Add the FAQ record to the template (see #221)
		$this->Template->faq = $objFaq->row();

		// Overwrite the page metadata (see #2853, #4955 and #87)
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if ($responseContext && $responseContext->has(HtmlHeadBag::class))
		{
			/** @var HtmlHeadBag $htmlHeadBag */
			$htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
			$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

			if ($objFaq->pageTitle)
			{
				$htmlHeadBag->setTitle($objFaq->pageTitle); // Already stored decoded
			}
			elseif ($objFaq->question)
			{
				$htmlHeadBag->setTitle($htmlDecoder->inputEncodedToPlainText($objFaq->question));
			}

			if ($objFaq->description)
			{
				$htmlHeadBag->setMetaDescription($htmlDecoder->inputEncodedToPlainText($objFaq->description));
			}
			elseif ($objFaq->question)
			{
				$htmlHeadBag->setMetaDescription($htmlDecoder->inputEncodedToPlainText($objFaq->question));
			}

			if ($objFaq->robots)
			{
				$htmlHeadBag->setMetaRobots($objFaq->robots);
			}
		}

		$this->Template->question = $objFaq->question;
		$this->Template->answer = StringUtil::encodeEmail($objFaq->answer);
		$this->Template->addImage = false;
		$this->Template->before = false;

		// Add image
		if ($objFaq->addImage)
		{
			$figure = System::getContainer()
				->get('contao.image.studio')
				->createFigureBuilder()
				->from($objFaq->singleSRC)
				->setSize($objFaq->size)
				->setMetadata($objFaq->getOverwriteMetadata())
				->enableLightbox($objFaq->fullsize)
				->buildIfResourceExists();

			$figure?->applyLegacyTemplateData($this->Template, null, $objFaq->floating);
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

		// schema.org information
		$this->Template->getSchemaOrgData = static function () use ($objFaq) {
			return ModuleFaq::getSchemaOrgData(array($objFaq));
		};

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
