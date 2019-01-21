<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Patchwork\Utf8;

/**
 * Front end module "newsletter list".
 *
 * @property array $nl_channels
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleNewsletterList extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_newsletterlist';

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
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['newsletterlist'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		$this->nl_channels = StringUtil::deserialize($this->nl_channels);

		// Return if there are no channels
		if (empty($this->nl_channels) || !\is_array($this->nl_channels))
		{
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

		$arrJumpTo = array();
		$arrNewsletter = array();

		$strRequest = ampersand(Environment::get('request'), true);
		$objNewsletter = NewsletterModel::findSentByPids($this->nl_channels);

		if ($objNewsletter !== null)
		{
			while ($objNewsletter->next())
			{
				/** @var NewsletterChannelModel $objTarget */
				if (!($objTarget = $objNewsletter->getRelated('pid')) instanceof NewsletterChannelModel)
				{
					continue;
				}

				$jumpTo = (int) $objTarget->jumpTo;

				// A jumpTo page is not mandatory for newsletter channels (see #6521) but required for the list module
				if ($jumpTo < 1)
				{
					throw new \Exception("Newsletter channels without redirect page cannot be used in a newsletter list");
				}

				$strUrl = $strRequest;

				if (!isset($arrJumpTo[$objTarget->jumpTo]))
				{
					if (($objJumpTo = $objTarget->getRelated('jumpTo')) instanceof PageModel)
					{
						/** @var PageModel $objJumpTo */
						$arrJumpTo[$objTarget->jumpTo] = $objJumpTo->getFrontendUrl(Config::get('useAutoItem') ? '/%s' : '/items/%s');
					}
					else
					{
						$arrJumpTo[$objTarget->jumpTo] = $strUrl;
					}
				}

				$strUrl = $arrJumpTo[$objTarget->jumpTo];
				$strAlias = $objNewsletter->alias ?: $objNewsletter->id;

				$arrNewsletter[] = array
				(
					'subject' => $objNewsletter->subject,
					'title' => StringUtil::stripInsertTags($objNewsletter->subject),
					'href' => sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), $strAlias),
					'date' => Date::parse($objPage->dateFormat, $objNewsletter->date),
					'datim' => Date::parse($objPage->datimFormat, $objNewsletter->date),
					'time' => Date::parse($objPage->timeFormat, $objNewsletter->date),
					'channel' => $objNewsletter->pid
				);
			}
		}

		$this->Template->newsletters = $arrNewsletter;
	}
}

class_alias(ModuleNewsletterList::class, 'ModuleNewsletterList');
