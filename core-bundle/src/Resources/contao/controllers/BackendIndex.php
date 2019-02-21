<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Security\Exception\LockedException;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Handle back end logins and logouts.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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

		if ($exception instanceof LockedException)
		{
			Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['accountLocked'], $exception->getLockedMinutes()));
		}
		elseif ($exception instanceof InvalidTwoFactorCodeException)
		{
			Message::addError($GLOBALS['TL_LANG']['ERR']['invalidTwoFactor']);
		}
		elseif ($exception instanceof AuthenticationException)
		{
			Message::addError($GLOBALS['TL_LANG']['ERR']['invalidLogin']);
		}

		$queryString = '';
		$arrParams = array();

		if ($referer = Input::get('referer', true))
		{
			$queryString = '?' . base64_decode($referer);
			$arrParams['referer'] = $referer;
		}

		$router = $container->get('router');

		$objTemplate = new BackendTemplate('be_login');
		$objTemplate->action = ampersand(Environment::get('request'));
		$objTemplate->headline = $GLOBALS['TL_LANG']['MSC']['loginBT'];

		/** @var TokenInterface $token */
		$token = $container->get('security.token_storage')->getToken();

		if ($token instanceof TwoFactorToken)
		{
			$objTemplate = new BackendTemplate('be_login_two_factor');
			$objTemplate->headline = $GLOBALS['TL_LANG']['MSC']['twoFactorAuthentication'];
			$objTemplate->action = $router->generate('contao_backend_two_factor');
			$objTemplate->authCode = $GLOBALS['TL_LANG']['MSC']['twoFactorVerification'];
			$objTemplate->cancel = $GLOBALS['TL_LANG']['MSC']['cancelBT'];
		}

		$objTemplate->theme = Backend::getTheme();
		$objTemplate->messages = Message::generate();
		$objTemplate->base = Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->languages = System::getLanguages(true); // backwards compatibility
		$objTemplate->title = Environment::get('host');
		$objTemplate->charset = Config::get('characterSet');
		$objTemplate->userLanguage = $GLOBALS['TL_LANG']['tl_user']['language'][0];
		$objTemplate->curLanguage = Input::post('language') ?: str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
		$objTemplate->curUsername = Input::post('username') ?: '';
		$objTemplate->loginButton = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['continue']);
		$objTemplate->username = $GLOBALS['TL_LANG']['tl_user']['username'][0];
		$objTemplate->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$objTemplate->feLink = $GLOBALS['TL_LANG']['MSC']['feLink'];
		$objTemplate->default = $GLOBALS['TL_LANG']['MSC']['default'];
		$objTemplate->jsDisabled = $GLOBALS['TL_LANG']['MSC']['jsDisabled'];
		$objTemplate->targetPath = StringUtil::specialchars($router->generate('contao_backend', array(), Router::ABSOLUTE_URL) . $queryString);
		$objTemplate->failurePath = StringUtil::specialchars($router->generate('contao_backend_login', $arrParams, Router::ABSOLUTE_URL));

		return $objTemplate->getResponse();
	}
}

class_alias(BackendIndex::class, 'BackendIndex');
