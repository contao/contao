<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Exception\AccessDeniedException;
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

		if (!\System::getContainer()->get('security.authorization_checker')->isGranted('ROLE_USER'))
		{
			throw new AccessDeniedException('Access denied');
		}

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
			$strUrl = \System::getContainer()->get('router')->generate('contao_root', array(), UrlGeneratorInterface::ABSOLUTE_URL);
		}

		$objTemplate->url = $strUrl;

		// Switch to a particular member (see #6546)
		if (\Input::get('user') && ($this->User->isAdmin || \is_array($this->User->amg) && !empty($this->User->amg)))
		{
			$objUser = \MemberModel::findByUsername(\Input::get('user'));

			// Check the allowed member groups
			if ($objUser !== null && ($this->User->isAdmin || \count(array_intersect(\StringUtil::deserialize($objUser->groups, true), $this->User->amg)) > 0))
			{
				$strHash = $this->getSessionHash('FE_USER_AUTH');

				// Set the cookie
				$this->setCookie('FE_USER_AUTH', $strHash, (time() + \Config::get('sessionTimeout')), null, null, \Environment::get('ssl'), true);
				$objTemplate->user = \Input::post('user');
			}
		}

		return $objTemplate->getResponse();
	}
}
