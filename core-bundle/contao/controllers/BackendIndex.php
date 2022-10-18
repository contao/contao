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
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

/**
 * Handle back end logins and logouts.
 */
class BackendIndex extends Backend
{
	/**
	 * Initialize the controller
	 *
	 * 1. Import the user
	 * 2. Call the parent constructor
	 * 3. Login the user
	 * 4. Load the language files
	 * DO NOT CHANGE THIS ORDER!
	 */
	public function __construct()
	{
		$this->import(BackendUser::class, 'User');
		parent::__construct();

		System::loadLanguageFile('default');
		System::loadLanguageFile('tl_user');
	}

	/**
	 * Run the controller and parse the login template
	 *
	 * @return Response
	 */
	public function run()
	{
		$container = System::getContainer();
		$exception = $container->get('security.authentication_utils')->getLastAuthenticationError();

		if ($exception instanceof TooManyLoginAttemptsAuthenticationException)
		{
			list('%minutes%' => $lockedMinutes) = $exception->getMessageData();
			Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['accountLocked'], $lockedMinutes));
		}
		elseif ($exception instanceof InvalidTwoFactorCodeException)
		{
			Message::addError($GLOBALS['TL_LANG']['ERR']['invalidTwoFactor']);
		}
		elseif ($exception instanceof AuthenticationException)
		{
			Message::addError($GLOBALS['TL_LANG']['ERR']['invalidLogin']);
		}

		$router = $container->get('router');
		$targetPath = $router->generate('contao_backend', array(), UrlGeneratorInterface::ABSOLUTE_URL);
		$request = $container->get('request_stack')->getCurrentRequest();

		if ($request && $request->query->has('redirect'))
		{
			/** @var UriSigner $uriSigner */
			$uriSigner = $container->get('uri_signer');

			// We cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
			if ($uriSigner->check($request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . (null !== ($qs = $request->server->get('QUERY_STRING')) ? '?' . $qs : '')))
			{
				$targetPath = $request->query->get('redirect');
			}
		}

		$objTemplate = new BackendTemplate('be_login');
		$objTemplate->headline = $GLOBALS['TL_LANG']['MSC']['loginBT'];

		/** @var TokenInterface $token */
		$token = $container->get('security.token_storage')->getToken();

		if ($token instanceof TwoFactorToken)
		{
			// Dispatch 2FA form event to prepare 2FA providers
			$event = new TwoFactorAuthenticationEvent($request, $token);
			$container->get('event_dispatcher')->dispatch($event, TwoFactorAuthenticationEvents::FORM);

			$objTemplate = new BackendTemplate('be_login_two_factor');
			$objTemplate->headline = $GLOBALS['TL_LANG']['MSC']['twoFactorAuthentication'];
			$objTemplate->authCode = $GLOBALS['TL_LANG']['MSC']['twoFactorVerification'];
			$objTemplate->cancel = $GLOBALS['TL_LANG']['MSC']['cancelBT'];
		}

		$objTemplate->theme = Backend::getTheme();
		$objTemplate->messages = Message::generate();
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->host = Backend::getDecodedHostname();
		$objTemplate->charset = System::getContainer()->getParameter('kernel.charset');
		$objTemplate->userLanguage = $GLOBALS['TL_LANG']['tl_user']['language'][0];
		$objTemplate->curUsername = Input::post('username') ?: '';
		$objTemplate->loginButton = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['continue']);
		$objTemplate->username = $GLOBALS['TL_LANG']['tl_user']['username'][0];
		$objTemplate->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$objTemplate->feLink = $GLOBALS['TL_LANG']['MSC']['feLink'];
		$objTemplate->default = $GLOBALS['TL_LANG']['MSC']['default'];
		$objTemplate->jsDisabled = $GLOBALS['TL_LANG']['MSC']['jsDisabled'];
		$objTemplate->targetPath = StringUtil::specialchars(base64_encode($targetPath));

		return $objTemplate->getResponse();
	}
}
