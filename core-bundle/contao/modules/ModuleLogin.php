<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

/**
 * Front end module "login".
 */
class ModuleLogin extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_login';

	/**
	 * Flash type
	 * @var string
	 */
	protected $strFlashType = 'contao.FE.error';

	/**
	 * @var string
	 */
	private $targetPath = '';

	/**
	 * Display a login form
	 *
	 * @return string
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['login'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

			return $objTemplate->parse();
		}

		// If the form was submitted and the credentials were wrong, take the target
		// path from the submitted data as otherwise it would take the current page
		if ($request && $request->isMethod('POST'))
		{
			$this->targetPath = base64_decode($request->request->get('_target_path'));
		}
		elseif ($this->redirectBack && $request && $request->query->has('redirect'))
		{
			$uriSigner = System::getContainer()->get('uri_signer');

			// We cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
			if ($uriSigner->check($request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . (null !== ($qs = $request->server->get('QUERY_STRING')) ? '?' . $qs : '')))
			{
				$this->targetPath = $request->query->get('redirect');
			}
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

		$container = System::getContainer();
		$request = $container->get('request_stack')->getCurrentRequest();
		$exception = null;
		$lastUsername = '';

		// Only call the authentication utils if there is an active session to prevent starting an empty session
		if ($request && $request->hasSession() && ($request->hasPreviousSession() || $request->getSession()->isStarted()))
		{
			$authUtils = $container->get('security.authentication_utils');
			$exception = $authUtils->getLastAuthenticationError();
			$lastUsername = $authUtils->getLastUsername();
		}

		$authorizationChecker = $container->get('security.authorization_checker');

		if ($authorizationChecker->isGranted('ROLE_MEMBER'))
		{
			$this->import(FrontendUser::class, 'User');

			$strRedirect = Environment::get('uri');

			// Redirect to last page visited
			if ($this->redirectBack && $this->targetPath)
			{
				$strRedirect = $this->targetPath;
			}

			// Redirect home if the page is protected
			elseif ($objPage->protected)
			{
				$strRedirect = Environment::get('base');
			}

			$this->Template->logout = true;
			$this->Template->formId = 'tl_logout_' . $this->id;
			$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['logout']);
			$this->Template->loggedInAs = sprintf($GLOBALS['TL_LANG']['MSC']['loggedInAs'], $this->User->username);
			$this->Template->action = $container->get('security.logout_url_generator')->getLogoutPath();
			$this->Template->targetPath = StringUtil::specialchars($strRedirect);

			if ($this->User->lastLogin > 0)
			{
				$this->Template->lastLogin = sprintf($GLOBALS['TL_LANG']['MSC']['lastLogin'][1], Date::parse($objPage->datimFormat, $this->User->lastLogin));
			}

			return;
		}

		if ($exception instanceof TooManyLoginAttemptsAuthenticationException)
		{
            ['%minutes%' => $lockedMinutes] = $exception->getMessageData();
			$this->Template->hasError = true;
			$this->Template->message = sprintf($GLOBALS['TL_LANG']['ERR']['accountLocked'], $lockedMinutes);
		}
		elseif ($exception instanceof InvalidTwoFactorCodeException)
		{
			$this->Template->hasError = true;
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['invalidTwoFactor'];
		}
		elseif ($exception instanceof AuthenticationException)
		{
			$this->Template->hasError = true;
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['invalidLogin'];
		}

		$blnRedirectBack = false;
		$strRedirect = Environment::get('uri');

		// Redirect to the last page visited
		if ($this->redirectBack && $this->targetPath)
		{
			$blnRedirectBack = true;
			$strRedirect = $this->targetPath;
		}

		// Redirect to the jumpTo page
		elseif (($objTarget = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$strRedirect = $objTarget->getAbsoluteUrl();
		}

		$this->Template->formId = 'tl_login_' . $this->id;
		$this->Template->forceTargetPath = (int) $blnRedirectBack;
		$this->Template->targetPath = StringUtil::specialchars(base64_encode($strRedirect));

		if ($authorizationChecker->isGranted('IS_AUTHENTICATED_2FA_IN_PROGRESS'))
		{
			// Dispatch 2FA form event to prepare 2FA providers
			$token = $container->get('security.token_storage')->getToken();
			$event = new TwoFactorAuthenticationEvent($request, $token);
			$container->get('event_dispatcher')->dispatch($event, TwoFactorAuthenticationEvents::FORM);

			$this->Template->twoFactorEnabled = true;
			$this->Template->authCode = $GLOBALS['TL_LANG']['MSC']['twoFactorVerification'];
			$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['continue']);
			$this->Template->cancel = $GLOBALS['TL_LANG']['MSC']['cancelBT'];
			$this->Template->twoFactorAuthentication = $GLOBALS['TL_LANG']['MSC']['twoFactorAuthentication'];

			return;
		}

		$this->Template->username = $GLOBALS['TL_LANG']['MSC']['username'];
		$this->Template->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['login']);
		$this->Template->value = Input::encodeInsertTags(StringUtil::specialchars($lastUsername));
		$this->Template->autologin = $this->autologin;
		$this->Template->autoLabel = $GLOBALS['TL_LANG']['MSC']['autologin'];
	}
}
