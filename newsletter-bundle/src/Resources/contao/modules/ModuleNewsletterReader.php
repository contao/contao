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
 * Front end module "newsletter reader".
 *
 * @property array $nl_channels
 */
class ModuleNewsletterReader extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_newsletterreader';

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
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['newsletterreader'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$this->nl_channels = StringUtil::deserialize($this->nl_channels);

		if (empty($this->nl_channels) || !\is_array($this->nl_channels))
		{
			throw new InternalServerErrorException('The newsletter reader ID ' . $this->id . ' has no channels specified.');
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$this->Template->content = '';

		if ($this->overviewPage)
		{
			$this->Template->referer = PageModel::findById($this->overviewPage)->getFrontendUrl();
			$this->Template->back = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['nl_overview'];
		}

		$objNewsletter = NewsletterModel::findSentByParentAndIdOrAlias(Input::get('auto_item'), $this->nl_channels);

		if (null === $objNewsletter)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Overwrite the page metadata (see #2853, #4955 and #87)
		if ($objNewsletter->subject)
		{
			$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

			if ($responseContext && $responseContext->has(HtmlHeadBag::class))
			{
				$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

				/** @var HtmlHeadBag $htmlHeadBag */
				$htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
				$htmlHeadBag->setTitle($htmlDecoder->inputEncodedToPlainText($objNewsletter->subject));
			}
		}

		// Add enclosure
		if ($objNewsletter->addFile)
		{
			$this->addEnclosuresToTemplate($this->Template, $objNewsletter->row(), 'files');
		}

		// Support plain text newsletters (thanks to Hagen Klemp)
		if ($objNewsletter->sendText)
		{
			$strContent = nl2br($objNewsletter->text, false);
		}
		else
		{
			$strContent = str_ireplace(' align="center"', '', $objNewsletter->content);
		}

		// Parse simple tokens and insert tags
		$strContent = System::getContainer()->get('contao.insert_tag.parser')->replace($strContent);
		$strContent = System::getContainer()->get('contao.string.simple_token_parser')->parse($strContent, array());

		// Encode e-mail addresses
		$strContent = StringUtil::encodeEmail($strContent);

		$this->Template->content = $strContent;
		$this->Template->subject = $objNewsletter->subject;

		// Tag the newsletter (see #2137)
		if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
		{
			$responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
			$responseTagger->addTags(array('contao.db.tl_newsletter.' . $objNewsletter->id));
		}
	}
}
