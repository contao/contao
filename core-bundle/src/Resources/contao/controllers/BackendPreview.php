<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Set up the front end preview frames.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BackendPreview extends Backend
{

	/**
	 * Initialize the controller
	 *
	 * 1. Import the user
	 * 2. Call the parent constructor
	 * 3. Authenticate the user
	 * 4. Load the language files
	 * DO NOT CHANGE THIS ORDER!
	 */
	public function __construct()
	{
		$this->import(BackendUser::class, 'User');
		parent::__construct();

		if (!System::getContainer()->get('security.authorization_checker')->isGranted('ROLE_USER'))
		{
			throw new AccessDeniedException('Access denied');
		}

		System::loadLanguageFile('default');
	}

	/**
	 * Run the controller and parse the template
	 *
	 * @return Response
	 */
	public function run()
	{
		$objRouter = System::getContainer()->get('router');

		// Switch to a particular member (see #6546)
		if ($strUser = Input::get('user'))
		{
			$objAuthenticator = System::getContainer()->get('contao.security.frontend_preview_authenticator');

			if (!$objAuthenticator->authenticateFrontendUser($strUser, false))
			{
				$objAuthenticator->removeFrontendAuthentication();
			}

			$arrParameters = array();

			if (Input::get('url'))
			{
				$arrParameters['url'] = Input::get('url');
			}

			if (Input::get('page'))
			{
				$arrParameters['page'] = Input::get('page');
			}

			return new RedirectResponse($objRouter->generate('contao_backend_preview', $arrParameters));
		}

		$objTemplate = new BackendTemplate('be_preview');
		$objTemplate->base = Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['fePreview']);
		$objTemplate->charset = Config::get('characterSet');
		$objTemplate->site = Input::get('site', true);
		$objTemplate->switchHref = $objRouter->generate('contao_backend_switch');
		$objTemplate->user = System::getContainer()->get('contao.security.token_checker')->getFrontendUsername();

		$strUrl = null;

		if (Input::get('url'))
		{
			$strUrl = Environment::get('base') . Input::get('url');
		}
		elseif (Input::get('page'))
		{
			$strUrl = $this->redirectToFrontendPage(Input::get('page'), Input::get('article'), true);
		}
		else
		{
			$event = new PreviewUrlConvertEvent();
			System::getContainer()->get('event_dispatcher')->dispatch(ContaoCoreEvents::PREVIEW_URL_CONVERT, $event);
			$strUrl = $event->getUrl();
		}

		if ($strUrl === null)
		{
			$strUrl = System::getContainer()->get('router')->generate('contao_root', array(), UrlGeneratorInterface::ABSOLUTE_URL);
		}

		$objTemplate->url = $strUrl;

		return $objTemplate->getResponse();
	}
}

class_alias(BackendPreview::class, 'BackendPreview');
