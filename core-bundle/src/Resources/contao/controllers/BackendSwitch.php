<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\HttpKernel\JwtManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Switch accounts in the front end preview.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BackendSwitch extends Backend
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
		$this->disableProfiler();

		if (Environment::get('isAjaxRequest'))
		{
			$this->getDatalistOptions();
		}

		$objJwtManager = null;

		if ($objRequest = System::getContainer()->get('request_stack')->getCurrentRequest())
		{
			$objJwtManager = $objRequest->attributes->get(JwtManager::ATTRIBUTE);
		}

		$blnCanSwitchUser = ($this->User->isAdmin || (!empty($this->User->amg) && \is_array($this->User->amg)));
		$objTokenChecker = System::getContainer()->get('contao.security.token_checker');
		$strUser = $objTokenChecker->getFrontendUsername();
		$blnShowUnpublished = $objTokenChecker->isPreviewMode();
		$blnDebug = System::getContainer()->get('kernel')->isDebug();
		$blnUpdate = false;

		// Switch
		if (Input::post('FORM_SUBMIT') == 'tl_switch')
		{
			$blnUpdate = true;
			$objAuthenticator = System::getContainer()->get('contao.security.frontend_preview_authenticator');
			$blnShowUnpublished = Input::post('unpublished') != 'hide';
			$blnDebug = (bool) Input::post('debug');

			// Switch user accounts
			if ($blnCanSwitchUser && isset($_POST['user']))
			{
				$strUser = Input::post('user');
			}

			if ($strUser)
			{
				$objAuthenticator->authenticateFrontendUser($strUser, $blnShowUnpublished);
			}
			else
			{
				$objAuthenticator->authenticateFrontendGuest($blnShowUnpublished);
			}
		}

		$objTemplate = new BackendTemplate('be_switch');
		$objTemplate->user = (string) $strUser;
		$objTemplate->show = $blnShowUnpublished;
		$objTemplate->update = $blnUpdate;
		$objTemplate->canSwitchUser = $blnCanSwitchUser;
		$objTemplate->canDebug = $this->User->isAdmin && $objJwtManager instanceof JwtManager;
		$objTemplate->theme = Backend::getTheme();
		$objTemplate->base = Environment::get('base');
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->apply = $GLOBALS['TL_LANG']['MSC']['apply'];
		$objTemplate->reload = $GLOBALS['TL_LANG']['MSC']['reload'];
		$objTemplate->feUser = $GLOBALS['TL_LANG']['MSC']['feUser'];
		$objTemplate->username = $GLOBALS['TL_LANG']['MSC']['username'];
		$objTemplate->charset = Config::get('characterSet');
		$objTemplate->lblHide = $GLOBALS['TL_LANG']['MSC']['hiddenHide'];
		$objTemplate->lblShow = $GLOBALS['TL_LANG']['MSC']['hiddenShow'];
		$objTemplate->fePreview = $GLOBALS['TL_LANG']['MSC']['fePreview'];
		$objTemplate->hiddenElements = $GLOBALS['TL_LANG']['MSC']['hiddenElements'];
		$objTemplate->debug = $blnDebug;
		$objTemplate->debugMode = $GLOBALS['TL_LANG']['MSC']['debugMode'];
		$objTemplate->lblEnabled = $GLOBALS['TL_LANG']['MSC']['debugEnabled'];
		$objTemplate->lblDisabled = $GLOBALS['TL_LANG']['MSC']['debugDisabled'];
		$objTemplate->action = ampersand(Environment::get('request'));

		$objResponse = $objTemplate->getResponse();

		if ($blnUpdate && $objJwtManager instanceof JwtManager)
		{
			$objJwtManager->addResponseCookie($objResponse, array('debug' => $blnDebug));
		}

		return $objResponse;
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
			if (empty($this->User->amg) || !\is_array($this->User->amg))
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
		$time = Date::floorToMinute();

		// Get the active front end users
		$objUsers = $this->Database->prepare("SELECT username FROM tl_member WHERE username LIKE ?$strGroups AND login='1' AND disable!='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'" . ($time + 60) . "') ORDER BY username")
								   ->limit(10)
								   ->execute(str_replace('%', '', Input::post('value')) . '%');

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
		$container = System::getContainer();

		if ($container->has('profiler'))
		{
			$container->get('profiler')->disable();
		}
	}
}

class_alias(BackendSwitch::class, 'BackendSwitch');
