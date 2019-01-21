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
 * Front end module "quick link".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleQuicklink extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_quicklink';

	/**
	 * Redirect to the selected page
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['quicklink'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Redirect to selected page
		if (Input::post('FORM_SUBMIT') == 'tl_quicklink_' . $this->id)
		{
			$this->redirect(Input::post('target', true));
		}

		// Always return an array (see #4616)
		$this->pages = StringUtil::deserialize($this->pages, true);

		if (empty($this->pages) || $this->pages[0] == '')
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

		// Get all active pages
		$objPages = PageModel::findPublishedRegularWithoutGuestsByIds($this->pages);

		// Return if there are no pages
		if ($objPages === null)
		{
			return;
		}

		$arrPages = array();

		// Sort the array keys according to the given order
		if ($this->orderPages != '')
		{
			$tmp = StringUtil::deserialize($this->orderPages);

			if (!empty($tmp) && \is_array($tmp))
			{
				$arrPages = array_map(function () {}, array_flip($tmp));
			}
		}

		// Add the items to the pre-sorted array
		while ($objPages->next())
		{
			$arrPages[$objPages->id] = $objPages->current();
		}

		$items = array();
		$arrPages = array_values(array_filter($arrPages));

		/** @var PageModel[] $arrPages */
		foreach ($arrPages as $objSubpage)
		{
			$objSubpage->title = StringUtil::stripInsertTags($objSubpage->title);
			$objSubpage->pageTitle = StringUtil::stripInsertTags($objSubpage->pageTitle);

			// Get href
			switch ($objSubpage->type)
			{
				case 'redirect':
					$href = $objSubpage->url;
					break;

				case 'forward':
					if (($objNext = $objSubpage->getRelated('jumpTo')) instanceof PageModel || ($objNext = PageModel::findFirstPublishedRegularByPid($objSubpage->id)) instanceof PageModel)
					{
						/** @var PageModel $objNext */
						$href = $objNext->getFrontendUrl();
						break;
					}
					// DO NOT ADD A break; STATEMENT

				default:
					$href = $objSubpage->getFrontendUrl();
					break;
			}

			$items[] = array
			(
				'href' => $href,
				'title' => StringUtil::specialchars($objSubpage->pageTitle ?: $objSubpage->title),
				'link' => $objSubpage->title,
				'active' => ($objPage->id == $objSubpage->id || ($objSubpage->type == 'forward' && $objPage->id == $objSubpage->jumpTo))
			);
		}

		$this->Template->items = $items;
		$this->Template->formId = 'tl_quicklink_' . $this->id;
		$this->Template->request = ampersand(Environment::get('request'), true);
		$this->Template->title = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['quicklink'];
		$this->Template->button = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['go']);
	}
}

class_alias(ModuleQuicklink::class, 'ModuleQuicklink');
