<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


/**
 * Switch accounts in the front end preview.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BackendSwitch extends \Backend
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
		$this->disableProfiler();

		if (\Environment::get('isAjaxRequest'))
		{
			$this->getDatalistOptions();
		}

		$strUser = '';
		$strHash = $this->getSessionHash('FE_USER_AUTH');
		$session = \System::getContainer()->get('session');

		// Get the front end user
		if ($session->has(\FrontendUser::SECURITY_SESSION_KEY) && ($token = unserialize($session->get(\FrontendUser::SECURITY_SESSION_KEY))) instanceof TokenInterface)
		{
			$strUser = $token->getUser()->getUsername();
		}

		$blnCanSwitchUser = ($this->User->isAdmin || (!empty($this->User->amg) && \is_array($this->User->amg)));

		/** @var BackendTemplate|object $objTemplate */
		$objTemplate = new \BackendTemplate('be_switch');
		$objTemplate->user = $strUser;
		$objTemplate->show = \Input::cookie('FE_PREVIEW');
		$objTemplate->update = false;
		$objTemplate->canSwitchUser = $blnCanSwitchUser;

		// Switch
		if (\Input::post('FORM_SUBMIT') == 'tl_switch')
		{
			$time = time();

			// Hide unpublished elements
			if (\Input::post('unpublished') == 'hide')
			{
				$this->setCookie('FE_PREVIEW', 0, ($time - 86400), null, null, \Environment::get('ssl'), true);
				$objTemplate->show = 0;
			}

			// Show unpublished elements
			else
			{
				$this->setCookie('FE_PREVIEW', 1, ($time + \Config::get('sessionTimeout')), null, null, \Environment::get('ssl'), true);
				$objTemplate->show = 1;
			}

			// Switch user accounts
			if ($blnCanSwitchUser)
			{
			   // Log in the front end user
				if (\Input::post('user'))
				{
					// Authenticate the new FrontendUser at the Symfony firewall
					\System::getContainer()->get('contao.security.frontend_preview_authenticator')->authenticateFrontendUser(\Input::post('user'));

					if ($session->has(\FrontendUser::SECURITY_SESSION_KEY) && ($token = unserialize($session->get(\FrontendUser::SECURITY_SESSION_KEY))) instanceof TokenInterface)
					{
						$objUser = \MemberModel::findByUsername($token->getUser()->getUsername());

						// Check the allowed member groups
						if ($objUser !== null && ($this->User->isAdmin || \count(array_intersect(\StringUtil::deserialize($objUser->groups, true), $this->User->amg)) > 0))
						{
							// Set the cookie
							$this->setCookie('FE_USER_AUTH', $strHash, ($time + \Config::get('sessionTimeout')), null, null, \Environment::get('ssl'), true);
							$objTemplate->user = \Input::post('user');
						}
						else
						{
							// Remove Symfony frontend authentication, if not allowed
							$session->remove(\FrontendUser::SECURITY_SESSION_KEY);
						}
					}
				}

				// Log out the front end user
				else
				{
					// Remove cookie
					$this->setCookie('FE_USER_AUTH', $strHash, ($time - 86400), null, null, \Environment::get('ssl'), true);
					$objTemplate->user = '';

					// Remove the Symfony frontend authentication token
					$session->remove(\FrontendUser::SECURITY_SESSION_KEY);
				}
			}

			$objTemplate->update = true;
		}

		// Default variables
		$objTemplate->theme = \Backend::getTheme();
		$objTemplate->base = \Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->apply = $GLOBALS['TL_LANG']['MSC']['apply'];
		$objTemplate->reload = $GLOBALS['TL_LANG']['MSC']['reload'];
		$objTemplate->feUser = $GLOBALS['TL_LANG']['MSC']['feUser'];
		$objTemplate->username = $GLOBALS['TL_LANG']['MSC']['username'];
		$objTemplate->charset = \Config::get('characterSet');
		$objTemplate->lblHide = $GLOBALS['TL_LANG']['MSC']['hiddenHide'];
		$objTemplate->lblShow = $GLOBALS['TL_LANG']['MSC']['hiddenShow'];
		$objTemplate->fePreview = $GLOBALS['TL_LANG']['MSC']['fePreview'];
		$objTemplate->hiddenElements = $GLOBALS['TL_LANG']['MSC']['hiddenElements'];
		$objTemplate->action = ampersand(\Environment::get('request'));

		return $objTemplate->getResponse();
	}


	/**
	 * Find ten matching usernames and return them as JSON
	 */
	protected function getDatalistOptions()
	{
		$strGroups = '';

		if (!$this->User->isAdmin)
		{
			// No allowed member groups
			if (!\is_array($this->User->amg) || empty($this->User->amg))
			{
				header('Content-type: application/json');
				die(json_encode(array()));
			}

			$arrGroups = array();

			foreach ($this->User->amg as $intGroup)
			{
				$arrGroups[] = '%"' . (int) $intGroup . '"%';
			}

			$strGroups = " AND (groups LIKE '" . implode("' OR GROUPS LIKE '", $arrGroups) . "')";
		}

		$arrUsers = array();
		$time = \Date::floorToMinute();

		// Get the active front end users
		$objUsers = $this->Database->prepare("SELECT username FROM tl_member WHERE username LIKE ?$strGroups AND login='1' AND disable!='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'" . ($time + 60) . "') ORDER BY username")
								   ->limit(10)
								   ->execute(str_replace('%', '', \Input::post('value')) . '%');

		if ($objUsers->numRows)
		{
			$arrUsers = $objUsers->fetchEach('username');
		}

		header('Content-type: application/json');
		die(json_encode($arrUsers));
	}


	/**
	 * Disable the profile
	 */
	private function disableProfiler()
	{
		$container = \System::getContainer();

		if ($container->has('profiler'))
		{
			$container->get('profiler')->disable();
		}
	}
}
