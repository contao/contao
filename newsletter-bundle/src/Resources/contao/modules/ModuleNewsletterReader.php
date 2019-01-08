<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Patchwork\Utf8;

/**
 * Front end module "newsletter reader".
 *
 * @property array $nl_channels
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['newsletterreader'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set the item from the auto_item parameter
		if (!isset($_GET['items']) && Config::get('useAutoItem') && isset($_GET['auto_item']))
		{
			Input::setGet('items', Input::get('auto_item'));
		}

		// Do not index or cache the page if no news item has been specified
		if (!Input::get('items'))
		{
			/** @var PageModel $objPage */
			global $objPage;

			$objPage->noSearch = 1;
			$objPage->cache = 0;

			return '';
		}

		$this->nl_channels = StringUtil::deserialize($this->nl_channels);

		// Do not index or cache the page if there are no channels
		if (empty($this->nl_channels) || !\is_array($this->nl_channels))
		{
			/** @var PageModel $objPage */
			global $objPage;

			$objPage->noSearch = 1;
			$objPage->cache = 0;

			return '';
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

		$this->Template->content = '';
		$this->Template->referer = 'javascript:history.go(-1)';
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

		$objNewsletter = NewsletterModel::findSentByParentAndIdOrAlias(Input::get('items'), $this->nl_channels);

		if (null === $objNewsletter)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Overwrite the page title (see #2853 and #4955)
		if ($objNewsletter->subject != '')
		{
			$objPage->pageTitle = strip_tags(StringUtil::stripInsertTags($objNewsletter->subject));
		}

		// Add enclosure
		if ($objNewsletter->addFile)
		{
			$this->addEnclosuresToTemplate($this->Template, $objNewsletter->row(), 'files');
		}

		// Support plain text newsletters (thanks to Hagen Klemp)
		if ($objNewsletter->sendText)
		{
			$strContent = nl2br_html5($objNewsletter->text);
		}
		else
		{
			$strContent = str_ireplace(' align="center"', '', $objNewsletter->content);
		}

		// Parse simple tokens and insert tags
		$strContent = $this->replaceInsertTags($strContent);
		$strContent = StringUtil::parseSimpleTokens($strContent, array());

		// Encode e-mail addresses
		$strContent = StringUtil::encodeEmail($strContent);

		$this->Template->content = $strContent;
		$this->Template->subject = $objNewsletter->subject;
	}
}

class_alias(ModuleNewsletterReader::class, 'ModuleNewsletterReader');
