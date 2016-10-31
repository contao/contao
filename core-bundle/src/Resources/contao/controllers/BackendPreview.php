<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * Set up the front end preview frames.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BackendPreview extends \Backend
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
		$this->import('BackendUser', 'User');
		parent::__construct();

		$this->User->authenticate();
		\System::loadLanguageFile('default');
	}


	/**
	 * Run the controller and parse the template
	 *
	 * @return Response
	 */
	public function run()
	{
		/** @var BackendTemplate|object $objTemplate */
		$objTemplate = new \BackendTemplate('be_preview');

		$objTemplate->base = \Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['fePreview']);
		$objTemplate->charset = \Config::get('characterSet');
		$objTemplate->site = \Input::get('site', true);
		$objTemplate->switchHref = \System::getContainer()->get('router')->generate('contao_backend_switch');

		$strUrl = null;

		if (\Input::get('url'))
		{
			$strUrl = \Environment::get('base') . \Input::get('url');
		}
		elseif (\Input::get('page'))
		{
			$strUrl = $this->redirectToFrontendPage(\Input::get('page'), \Input::get('article'), true);
		}
		else
		{
			$event = new PreviewUrlConvertEvent();
			\System::getContainer()->get('event_dispatcher')->dispatch(ContaoCoreEvents::PREVIEW_URL_CONVERT, $event);
			$strUrl = $event->getUrl();
		}

		if ($strUrl === null)
		{
			$strUrl = \System::getContainer()->get('router')->generate('contao_root', [], UrlGeneratorInterface::ABSOLUTE_URL);
		}

		$objTemplate->url = $strUrl;

		// Switch to a particular member (see #6546)
		if (\Input::get('user') && $this->User->isAdmin)
		{
			$objUser = \MemberModel::findByUsername(\Input::get('user'));

			if ($objUser !== null)
			{
				$strHash = $this->getSessionHash('FE_USER_AUTH');

				// Remove old sessions
				$this->Database->prepare("DELETE FROM tl_session WHERE tstamp<? OR hash=?")
							   ->execute((time() - \Config::get('sessionTimeout')), $strHash);

				// Insert the new session
				$this->Database->prepare("INSERT INTO tl_session (pid, tstamp, name, sessionID, ip, hash) VALUES (?, ?, ?, ?, ?, ?)")
							   ->execute($objUser->id, time(), 'FE_USER_AUTH', \System::getContainer()->get('session')->getId(), \Environment::get('ip'), $strHash);

				// Set the cookie
				$this->setCookie('FE_USER_AUTH', $strHash, (time() + \Config::get('sessionTimeout')), null, null, \Environment::get('ssl'), true);
				$objTemplate->user = \Input::post('user');
			}
		}

		return $objTemplate->getResponse();
	}
}
