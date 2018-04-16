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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
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
		$this->import('BackendUser', 'User');
		parent::__construct();

		\System::loadLanguageFile('default');
		\System::loadLanguageFile('tl_user');
	}


	/**
	 * Run the controller and parse the login template
	 *
	 * @return Response
	 */
	public function run()
	{
		$exception = \System::getContainer()->get('security.authentication_utils')->getLastAuthenticationError();

		if ($exception instanceof LockedException)
		{
			\Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['accountLocked'], $exception->getLockedMinutes()));
		}
		elseif ($exception instanceof AuthenticationException)
		{
			\Message::addError($GLOBALS['TL_LANG']['ERR']['invalidLogin']);
		}

		$queryString = '';
		$arrParams = array();

		if ($referer = \Input::get('referer', true))
		{
			$queryString = '?' . base64_decode($referer);
			$arrParams['referer'] = $referer;
		}

		$router = \System::getContainer()->get('router');

		/** @var BackendTemplate|object $objTemplate */
		$objTemplate = new \BackendTemplate('be_login');

		$objTemplate->theme = \Backend::getTheme();
		$objTemplate->messages = \Message::generate();
		$objTemplate->base = \Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->languages = \System::getLanguages(true); // backwards compatibility
		$objTemplate->title = \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['loginTo'], \Config::get('websiteTitle')));
		$objTemplate->charset = \Config::get('characterSet');
		$objTemplate->action = ampersand(\Environment::get('request'));
		$objTemplate->userLanguage = $GLOBALS['TL_LANG']['tl_user']['language'][0];
		$objTemplate->headline = $GLOBALS['TL_LANG']['MSC']['loginBT'];
		$objTemplate->curLanguage = \Input::post('language') ?: str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
		$objTemplate->curUsername = \Input::post('username') ?: '';
		$objTemplate->loginButton = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['continue']);
		$objTemplate->username = $GLOBALS['TL_LANG']['tl_user']['username'][0];
		$objTemplate->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$objTemplate->feLink = $GLOBALS['TL_LANG']['MSC']['feLink'];
		$objTemplate->default = $GLOBALS['TL_LANG']['MSC']['default'];
		$objTemplate->jsDisabled = $GLOBALS['TL_LANG']['MSC']['jsDisabled'];
		$objTemplate->targetPath = \StringUtil::specialchars($router->generate('contao_backend', array(), Router::ABSOLUTE_URL) . $queryString);
		$objTemplate->failurePath = \StringUtil::specialchars($router->generate('contao_backend_login', $arrParams, Router::ABSOLUTE_URL));

		return $objTemplate->getResponse();
	}
}

class_alias(BackendIndex::class, 'BackendIndex');
