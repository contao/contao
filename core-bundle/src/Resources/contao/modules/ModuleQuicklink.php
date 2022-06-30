<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * Front end module "quick link".
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
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['quicklink'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		// Redirect to selected page
		if (Input::post('FORM_SUBMIT') == 'tl_quicklink_' . $this->id)
		{
			$this->redirect(Input::post('target', true));
		}

		// Always return an array (see #4616)
		$this->pages = StringUtil::deserialize($this->pages, true);

		if (empty($this->pages) || !$this->pages[0])
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
		$objPages = PageModel::findPublishedRegularByIds($this->pages);

		// Return if there are no pages
		if ($objPages === null)
		{
			return;
		}

		$items = array();
		$container = System::getContainer();
		$security = $container->get('security.helper');
		$isMember = $security->isGranted('ROLE_MEMBER');

		/** @var PageModel[] $objPages */
		foreach ($objPages as $objSubpage)
		{
			$objSubpage->loadDetails();

			// PageModel->groups is an array after calling loadDetails()
			if (!$objSubpage->protected || $this->showProtected || $security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $objSubpage->groups))
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
						if ($objSubpage->jumpTo)
						{
							$objNext = PageModel::findPublishedById($objSubpage->jumpTo);
						}
						else
						{
							$objNext = PageModel::findFirstPublishedRegularByPid($objSubpage->id);
						}

						if ($objNext instanceof PageModel)
						{
							$href = $objNext->getFrontendUrl();
							break;
						}
						// no break

					default:
						try
						{
							$href = $objSubpage->getFrontendUrl();
						}
						catch (ExceptionInterface $exception)
						{
							continue 2;
						}
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
		}

		$this->Template->items = $items;
		$this->Template->formId = 'tl_quicklink_' . $this->id;
		$this->Template->request = StringUtil::ampersand(Environment::get('requestUri'));
		$this->Template->title = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['quicklink'];
		$this->Template->button = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['go']);
	}
}
