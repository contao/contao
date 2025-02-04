<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Nyholm\Psr7\Uri;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

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

		$user = System::getContainer()->get('security.helper')->getUser();

		if ($user && !$user instanceof FrontendUser)
		{
			return '';
		}

		// If the form was submitted and the credentials were wrong, take the target
		// path from the submitted data as otherwise it would take the current page
		if ($request?->isMethod('POST'))
		{
			$this->targetPath = base64_decode($request->request->get('_target_path'));
		}
		elseif ($request?->query->has('redirect'))
		{
			$uriSigner = System::getContainer()->get('uri_signer');

			// We cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
			if ($uriSigner->check($request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . (null !== ($qs = $request->server->get('QUERY_STRING')) ? '?' . $qs : '')))
			{
				$this->targetPath = $request->query->get('redirect');
			}
		}
		elseif ($referer = $request?->headers->get('referer'))
		{
			$refererUri = new Uri($referer);
			$requestUri = new Uri($request->getUri());

			// Use the HTTP referer as a fallback, but only if scheme and host matches with the current request (see #5860)
			if ($refererUri->getScheme() === $requestUri->getScheme() && $refererUri->getHost() === $requestUri->getHost() && $refererUri->getPort() === $requestUri->getPort())
			{
				$this->targetPath = (string) $refererUri;
			}
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		global $objPage;

		$container = System::getContainer();
		$request = $container->get('request_stack')->getCurrentRequest();
		$security = $container->get('security.helper');
		$user = $security->getUser();
		$exception = null;
		$lastUsername = '';
		$isRemembered = $security->isGranted('IS_REMEMBERED');
		$isTwoFactorInProgress = $security->isGranted('IS_AUTHENTICATED_2FA_IN_PROGRESS');

		// The user can re-authenticate on the error_401 page or on the redirect page of the error_401 page
		$canReauthenticate = $objPage?->type == 'error_401' || ($this->targetPath && $this->targetPath === $request?->query->get('redirect'));

		// Show the logout button if the user is fully authenticated or cannot re-authenticate on the current page
		if ($user instanceof FrontendUser && !$isTwoFactorInProgress && (!$isRemembered || !$canReauthenticate))
		{
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
			$this->Template->loggedInAs = \sprintf($GLOBALS['TL_LANG']['MSC']['loggedInAs'], $user->getUserIdentifier());
			$this->Template->action = $container->get('security.logout_url_generator')->getLogoutPath();
			$this->Template->targetPath = StringUtil::specialchars($strRedirect);

			if ($user->lastLogin > 0)
			{
				$this->Template->lastLogin = \sprintf($GLOBALS['TL_LANG']['MSC']['lastLogin'][1], Date::parse($objPage->datimFormat, $user->lastLogin));
			}

			return;
		}

		// Only call the authentication utils if there is an active session to prevent starting an empty session
		if ($request?->hasSession() && ($request->hasPreviousSession() || $request->getSession()->isStarted()))
		{
			$authUtils = $container->get('security.authentication_utils');
			$exception = $authUtils->getLastAuthenticationError();
			$lastUsername = $authUtils->getLastUsername();

			// Store exception and username again to make it available for additional login modules on the page (see contao/contao#6275)
			if ($exception)
			{
				$request->attributes->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
			}

			if ($lastUsername)
			{
				$request->attributes->set(SecurityRequestAttributes::LAST_USERNAME, $lastUsername);
			}
		}

		if ($exception instanceof TooManyLoginAttemptsAuthenticationException)
		{
			$this->Template->hasError = true;
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['tooManyLoginAttempts'];
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
		elseif ($objTarget = PageModel::findById($this->objModel->jumpTo))
		{
			$strRedirect = $container->get('contao.routing.content_url_generator')->generate($objTarget, array(), UrlGeneratorInterface::ABSOLUTE_URL);
		}

		$this->Template->formId = 'tl_login_' . $this->id;
		$this->Template->forceTargetPath = (int) $blnRedirectBack;
		$this->Template->targetPath = StringUtil::specialchars(base64_encode($strRedirect));

		if ($isTwoFactorInProgress && $request)
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

		if ($pwResetPage = PageModel::findById($this->objModel->pwResetPage))
		{
			$this->Template->pwResetUrl = System::getContainer()->get('contao.routing.content_url_generator')->generate($pwResetPage);
		}

		$this->Template->username = $GLOBALS['TL_LANG']['MSC']['username'];
		$this->Template->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['login']);
		$this->Template->value = Input::encodeInsertTags(StringUtil::specialchars($lastUsername));
		$this->Template->autologin = $this->autologin;
		$this->Template->autoLabel = $GLOBALS['TL_LANG']['MSC']['autologin'];
		$this->Template->remembered = false;

		if ($isRemembered)
		{
			$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['verify']);
			$this->Template->loggedInAs = \sprintf($GLOBALS['TL_LANG']['MSC']['loggedInAs'], $user->getUserIdentifier());
			$this->Template->reauthenticate = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['reauthenticate']);
			$this->Template->value = Input::encodeInsertTags(StringUtil::specialchars($user->getUserIdentifier()));
			$this->Template->remembered = true;
		}
	}
}
