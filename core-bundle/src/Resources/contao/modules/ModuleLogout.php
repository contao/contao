<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Request;

trigger_deprecation('contao/core-bundle', '4.2', 'Using the logout module has been deprecated and will no longer work in Contao 5.0. Use the logout page instead.');

/**
 * Front end module "logout".
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
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		// Do not render the logout module on the command line, there cannot be a firewall or user logged in
		if (!$request)
		{
			return '';
		}

		if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['logout'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		$strRedirect = Environment::get('base');

		// Redirect to last page visited
		if ($this->redirectBack && ($strReferer = $this->getReferer()))
		{
			$strRedirect = $strReferer;
		}

		// Redirect to jumpTo page
		elseif (($objTarget = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$strRedirect = $objTarget->getAbsoluteUrl();
		}

		$pairs = array();
		$strLogoutUrl = System::getContainer()->get('security.logout_url_generator')->getLogoutUrl();
		$request = Request::create($strLogoutUrl);

		if ($request->server->has('QUERY_STRING'))
		{
			parse_str($request->server->get('QUERY_STRING'), $pairs);
		}

		// Add the redirect= parameter to the logout URL
		$pairs['redirect'] = $strRedirect;

		$uri = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . '?' . http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);

		$this->redirect($uri);

		return '';
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
	}
}

class_alias(ModuleLogout::class, 'ModuleLogout');
