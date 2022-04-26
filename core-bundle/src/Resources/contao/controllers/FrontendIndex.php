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
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\HttpFoundation\Response;

/**
 * Main front end controller.
 */
class FrontendIndex extends Frontend
{
	/**
	 * Initialize the object
	 */
	public function __construct()
	{
		// Load the user object before calling the parent constructor
		$this->import(FrontendUser::class, 'User');
		parent::__construct();
	}

	/**
	 * Render a page
	 *
	 * @return Response
	 *
	 * @throws \LogicException
	 * @throws PageNotFoundException
	 * @throws AccessDeniedException
	 */
	public function renderPage(PageModel $pageModel)
	{
		/** @var PageModel $objPage */
		global $objPage;

		$objPage = $pageModel;

		// Inherit the settings from the parent pages
		$objPage->loadDetails();

		// Set the admin e-mail address
		if ($objPage->adminEmail)
		{
			list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = StringUtil::splitFriendlyEmail($objPage->adminEmail);
		}
		else
		{
			list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = StringUtil::splitFriendlyEmail(Config::get('adminEmail'));
		}

		// Authenticate the user if the page is protected
		if ($objPage->protected)
		{
			$security = System::getContainer()->get('security.helper');

			if (!$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $objPage->groups))
			{
				if (($token = $security->getToken()) === null || System::getContainer()->get('security.authentication.trust_resolver')->isAnonymous($token))
				{
					throw new InsufficientAuthenticationException('Not authenticated: ' . Environment::get('uri'));
				}

				$user = $security->getUser();

				if ($user instanceof FrontendUser)
				{
					System::getContainer()->get('monolog.logger.contao.error')->error('Page ID "' . $objPage->id . '" can only be accessed by groups "' . implode(', ', $objPage->groups) . '" (current user groups: ' . implode(', ', StringUtil::deserialize($user->groups, true)) . ')');
				}

				throw new AccessDeniedException('Access denied: ' . Environment::get('uri'));
			}
		}

		// Backup some globals (see #7659)
		$arrHead = $GLOBALS['TL_HEAD'] ?? null;
		$arrBody = $GLOBALS['TL_BODY'] ?? null;
		$arrMootools = $GLOBALS['TL_MOOTOOLS'] ?? null;
		$arrJquery = $GLOBALS['TL_JQUERY'] ?? null;

		try
		{
			$pageType = $GLOBALS['TL_PTY'][$objPage->type] ?? PageRegular::class;
			$objHandler = new $pageType();

			return $objHandler->getResponse($objPage, true);
		}

		// Render the error page (see #5570)
		catch (UnusedArgumentsException $e)
		{
			// Restore the globals (see #7659)
			$GLOBALS['TL_HEAD'] = $arrHead;
			$GLOBALS['TL_BODY'] = $arrBody;
			$GLOBALS['TL_MOOTOOLS'] = $arrMootools;
			$GLOBALS['TL_JQUERY'] = $arrJquery;

			throw $e;
		}
	}
}
