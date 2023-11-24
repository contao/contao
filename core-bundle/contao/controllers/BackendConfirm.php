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
use Symfony\Component\HttpFoundation\Response;

/**
 * Confirm an invalid token URL.
 */
class BackendConfirm extends Backend
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
		parent::__construct();

		if (!System::getContainer()->get('security.authorization_checker')->isGranted('ROLE_USER'))
		{
			throw new AccessDeniedException('Access denied');
		}

		System::loadLanguageFile('default');
		System::loadLanguageFile('modules');
	}

	/**
	 * Run the controller
	 *
	 * @return Response
	 */
	public function run()
	{
		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Redirect to the back end home page
		if (Input::post('FORM_SUBMIT') == 'invalid_token_url')
		{
			list($strUrl) = explode('?', $objSession->get('INVALID_TOKEN_URL'));
			$this->redirect($strUrl);
		}

		$objTemplate = new BackendTemplate('be_confirm');

		// Prepare the URL
		$url = preg_replace('/[?&]rt=[^&]*/', '', $objSession->get('INVALID_TOKEN_URL'));
		$objTemplate->href = StringUtil::ampersand($url . ((strpos($url, '?') !== false) ? '&rt=' : '?rt=') . htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue()));

		$vars = array();
		list(, $request) = explode('?', $url, 2);

		// Extract the arguments
		foreach (explode('&', $request) as $arg)
		{
			list($key, $value) = explode('=', $arg, 2);
			$vars[$key] = $value;
		}

		// A valid back end request must point to a back end module
		if (empty($vars['do']))
		{
			$this->redirect(System::getContainer()->get('router')->generate('contao_backend'));
		}

		$arrInfo = array();

		// Provide more information about the link (see #4007)
		foreach ($vars as $k=>$v)
		{
			switch ($k)
			{
				default:
					$arrInfo[$k] = $v;
					break;

				case 'do':
					$arrInfo['do'] = $GLOBALS['TL_LANG']['MOD'][$v][0] ?? $v;
					break;

				case 'id':
					$arrInfo['id'] = 'ID ' . $v;
					break;
			}
		}

		// Use the first table if none is given
		if (!isset($arrInfo['table']))
		{
			foreach ($GLOBALS['BE_MOD'] as $category=>$modules)
			{
				if (isset($GLOBALS['BE_MOD'][$category][$vars['do']]))
				{
					$arrInfo['table'] = $GLOBALS['BE_MOD'][$category][$vars['do']]['tables'][0];
					break;
				}
			}
		}

		if (!empty($arrInfo['table']))
		{
			System::loadLanguageFile($arrInfo['table']);
		}

		// Override the action label
		if (isset($arrInfo['clipboard']))
		{
			$arrInfo['act'] = $GLOBALS['TL_LANG']['MSC']['clearClipboard'];
		}
		elseif (isset($arrInfo['mode']) && !isset($arrInfo['act']))
		{
			if ($arrInfo['mode'] == 'create')
			{
				$arrInfo['act'] = $GLOBALS['TL_LANG'][$arrInfo['table']]['new'][0];
			}
			elseif ($arrInfo['mode'] == 'cut' || $arrInfo['mode'] == 'copy')
			{
				$arrInfo['act'] = $GLOBALS['TL_LANG'][$arrInfo['table']][$arrInfo['mode']][0];
			}
		}
		elseif ($arrInfo['act'] == 'select' && isset($GLOBALS['TL_LANG']['MSC']['all']))
		{
			$arrInfo['act'] = \is_array($GLOBALS['TL_LANG']['MSC']['all']) ? $GLOBALS['TL_LANG']['MSC']['all'][0] : $GLOBALS['TL_LANG']['MSC']['all'];
		}
		elseif (!empty($GLOBALS['TL_LANG'][$arrInfo['table']][$arrInfo['act']]))
		{
			$arrInfo['act'] = \is_array($GLOBALS['TL_LANG'][$arrInfo['table']][$arrInfo['act']]) ? $GLOBALS['TL_LANG'][$arrInfo['table']][$arrInfo['act']][0] : $GLOBALS['TL_LANG'][$arrInfo['table']][$arrInfo['act']];
		}

		// Replace the ID wildcard
		if (strpos($arrInfo['act'], '%s') !== false)
		{
			$arrInfo['act'] = sprintf($arrInfo['act'], $vars['id']);
		}

		unset($arrInfo['pid'], $arrInfo['clipboard'], $arrInfo['ref'], $arrInfo['mode']);

		// Template variables
		$objTemplate->confirm = true;
		$objTemplate->link = StringUtil::specialchars($url);
		$objTemplate->info = $arrInfo;
		$objTemplate->labels = $GLOBALS['TL_LANG']['CONFIRM'];
		$objTemplate->explain = $GLOBALS['TL_LANG']['MSC']['invalidTokenUrl'];
		$objTemplate->cancel = $GLOBALS['TL_LANG']['MSC']['cancelBT'];
		$objTemplate->continue = $GLOBALS['TL_LANG']['MSC']['continue'];
		$objTemplate->theme = Backend::getTheme();
		$objTemplate->language = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->h1 = $GLOBALS['TL_LANG']['MSC']['invalidToken'];
		$objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['invalidToken']);
		$objTemplate->host = Backend::getDecodedHostname();
		$objTemplate->charset = System::getContainer()->getParameter('kernel.charset');

		return $objTemplate->getResponse();
	}
}
