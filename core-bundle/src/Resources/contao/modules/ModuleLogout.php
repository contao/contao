<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use League\Uri\Components\Query;
use League\Uri\Http;
use Patchwork\Utf8;

@trigger_error('Using the logout module has been deprecated and will no longer work in Contao 5.0. Use the logout page instead.', E_USER_DEPRECATED);

/**
 * Front end module "logout".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.0.
 *             Use the logout page instead.
 */
class ModuleLogout extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate;

	/**
	 * Logout the current user and redirect
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['logout'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set last page visited
		if ($this->redirectBack)
		{
			$_SESSION['LAST_PAGE_VISITED'] = $this->getReferer();
		}

		$strLogoutUrl = System::getContainer()->get('security.logout_url_generator')->getLogoutUrl();
		$strRedirect = Environment::get('base');

		// Redirect to last page visited
		if ($this->redirectBack && !empty($_SESSION['LAST_PAGE_VISITED']))
		{
			$strRedirect = $_SESSION['LAST_PAGE_VISITED'];
		}

		// Redirect to jumpTo page
		elseif ($this->jumpTo && ($objTarget = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$strRedirect = $objTarget->getAbsoluteUrl();
		}

		$uri = Http::createFromString($strLogoutUrl);

		// Add the redirect= parameter to the logout URL
		$query = new Query($uri->getQuery());
		$query = $query->merge('redirect=' . $strRedirect);

		$this->redirect((string) $uri->withQuery((string) $query));

		return '';
	}

	/**
	 * Generate the module
	 */
	protected function compile() {}
}

class_alias(ModuleLogout::class, 'ModuleLogout');
