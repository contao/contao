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
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Main front end controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
	 * Run the controller
	 *
	 * @return Response
	 *
	 * @throws PageNotFoundException
	 */
	public function run()
	{
		$pageId = $this->getPageIdFromUrl();

		// Load a website root page object if there is no page ID
		if ($pageId === null)
		{
			$objRootPage = $this->getRootPageFromUrl();

			/** @var PageRoot $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['root']();
			$pageId = $objHandler->generate($objRootPage->id, true, true);
		}

		// Throw a 404 error if the request is not a Contao request (see #2864)
		elseif ($pageId === false)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		$pageModel = PageModel::findPublishedByIdOrAlias($pageId);

		// Throw a 404 error if the page could not be found
		if ($pageModel === null)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		return $this->renderPage($pageModel);
	}

	/**
	 * Render a page
	 *
	 * @param Collection|PageModel[]|PageModel $pageModel
	 *
	 * @return Response
	 *
	 * @throws \LogicException
	 * @throws PageNotFoundException
	 * @throws AccessDeniedException
	 */
	public function renderPage($pageModel)
	{
		global $objPage;

		$objPage = $pageModel;

		// Check the URL and language of each page if there are multiple results
		if ($objPage instanceof Collection && $objPage->count() > 1)
		{
			@trigger_error('Using FrontendIndex::renderPage() with a model collection has been deprecated and will no longer work Contao 5.0. Use the Symfony routing instead.', E_USER_DEPRECATED);

			$arrPages = array();

			// Order by domain and language
			while ($objPage->next())
			{
				/** @var PageModel $objModel */
				$objModel = $objPage->current();
				$objCurrentPage = $objModel->loadDetails();

				$domain = $objCurrentPage->domain ?: '*';
				$arrPages[$domain][$objCurrentPage->rootLanguage] = $objCurrentPage;

				// Also store the fallback language
				if ($objCurrentPage->rootIsFallback)
				{
					$arrPages[$domain]['*'] = $objCurrentPage;
				}
			}

			$strHost = Environment::get('host');

			// Look for a root page whose domain name matches the host name
			$arrLangs = $arrPages[$strHost] ?? ($arrPages['*'] ?: array());

			// Throw an exception if there are no matches (see #1522)
			if (empty($arrLangs))
			{
				throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
			}

			// Use the first result (see #4872)
			if (!Config::get('addLanguageToUrl'))
			{
				$objNewPage = current($arrLangs);
			}

			// Try to find a page matching the language parameter
			elseif (($lang = Input::get('language')) && isset($arrLangs[$lang]))
			{
				$objNewPage = $arrLangs[$lang];
			}

			// Use the fallback language (see #8142)
			elseif (isset($arrLangs['*']))
			{
				$objNewPage = $arrLangs['*'];
			}

			// Throw an exception if there is no matching page (see #1522)
			else
			{
				throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
			}

			// Store the page object
			if (\is_object($objNewPage))
			{
				$objPage = $objNewPage;
			}
		}

		// Throw a 500 error if the result is still ambiguous
		if ($objPage instanceof Collection && $objPage->count() > 1)
		{
			$this->log('More than one page matches ' . Environment::get('base') . Environment::get('request'), __METHOD__, TL_ERROR);

			throw new \LogicException('More than one page found: ' . Environment::get('uri'));
		}

		// Make sure $objPage is a Model
		if ($objPage instanceof Collection)
		{
			$objPage = $objPage->current();
		}

		// If the page has an alias, it can no longer be called via ID (see #7661)
		if ($objPage->alias)
		{
			$language = Config::get('addLanguageToUrl') ? '[a-z]{2}(-[A-Z]{2})?/' : '';
			$suffix = preg_quote(Config::get('urlSuffix'), '#');

			if (preg_match('#^' . $language . $objPage->id . '(' . $suffix . '($|\?)|/)#', Environment::get('relativeRequest')))
			{
				throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
			}
		}

		// Trigger the 404 page if an item is required but not given (see #8361)
		if ($objPage->requireItem)
		{
			$hasItem = false;

			if (Config::get('useAutoItem'))
			{
				$hasItem = isset($_GET['auto_item']);
			}
			else
			{
				foreach ($GLOBALS['TL_AUTO_ITEM'] as $item)
				{
					if (isset($_GET[$item]))
					{
						$hasItem = true;
						break;
					}
				}
			}

			if (!$hasItem)
			{
				throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
			}
		}

		// Inherit the settings from the parent pages
		$objPage->loadDetails();

		// Trigger the 404 page if the page is not published and the front end preview is not active (see #374)
		if (!BE_USER_LOGGED_IN && !$objPage->isPublic)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Load a website root page object (will redirect to the first active regular page)
		if ($objPage->type == 'root')
		{
			/** @var PageRoot $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['root']();

			throw new ResponseException($objHandler->getResponse($objPage->id));
		}

		// Set the admin e-mail address
		if ($objPage->adminEmail)
		{
			list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = StringUtil::splitFriendlyEmail($objPage->adminEmail);
		}
		else
		{
			list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = StringUtil::splitFriendlyEmail(Config::get('adminEmail'));
		}

		// Exit if the root page has not been published (see #2425)
		// Do not try to load the 404 page, it can cause an infinite loop!
		if (!BE_USER_LOGGED_IN && !$objPage->rootIsPublic)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Check wether the language matches the root page language
		if (isset($_GET['language']) && Config::get('addLanguageToUrl') && Input::get('language') != $objPage->rootLanguage)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Check whether there are domain name restrictions
		if ($objPage->domain && $objPage->domain != Environment::get('host'))
		{
			$this->log('Page ID "' . $objPage->id . '" was requested via "' . Environment::get('host') . '" but can only be accessed via "' . $objPage->domain . '" (' . Environment::get('base') . Environment::get('request') . ')', __METHOD__, TL_ERROR);

			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Authenticate the user if the page is protected
		if ($objPage->protected)
		{
			$container = System::getContainer();
			$token = $container->get('security.token_storage')->getToken();

			if ($container->get('security.authentication.trust_resolver')->isAnonymous($token))
			{
				throw new InsufficientAuthenticationException('Not authenticated: ' . Environment::get('uri'));
			}

			if (!$container->get('security.authorization_checker')->isGranted('ROLE_MEMBER'))
			{
				throw new AccessDeniedException('Access denied: ' . Environment::get('uri'));
			}

			$arrGroups = $objPage->groups; // required for empty()

			// Check the user groups
			if (empty($arrGroups) || !\is_array($arrGroups) || !\is_array($this->User->groups) || !\count(array_intersect($arrGroups, $this->User->groups)))
			{
				$this->log('Page ID "' . $objPage->id . '" can only be accessed by groups "' . implode(', ', (array) $objPage->groups) . '" (current user groups: ' . implode(', ', $this->User->groups) . ')', __METHOD__, TL_ERROR);

				throw new AccessDeniedException('Access denied: ' . Environment::get('uri'));
			}
		}

		// Backup some globals (see #7659)
		$arrBackup = array(
			$GLOBALS['TL_HEAD'] ?? array(),
			$GLOBALS['TL_BODY'] ?? array(),
			$GLOBALS['TL_MOOTOOLS'] ?? array(),
			$GLOBALS['TL_JQUERY'] ?? array(),
			$GLOBALS['TL_USER_CSS'] ?? array(),
			$GLOBALS['TL_FRAMEWORK_CSS'] ?? array()
		);

		try
		{
			// Generate the page
			switch ($objPage->type)
			{
				case 'error_401':
					$objHandler = new $GLOBALS['TL_PTY']['error_401']();

					/** @var PageError401 $objHandler */
					return $objHandler->getResponse($objPage->rootId);

				case 'error_403':
					$objHandler = new $GLOBALS['TL_PTY']['error_403']();

					/** @var PageError403 $objHandler */
					return $objHandler->getResponse($objPage->rootId);

				case 'error_404':
					$objHandler = new $GLOBALS['TL_PTY']['error_404']();

					/** @var PageError404 $objHandler */
					return $objHandler->getResponse();

				default:
					$objHandler = new $GLOBALS['TL_PTY'][$objPage->type]();

					// Backwards compatibility
					if (!method_exists($objHandler, 'getResponse'))
					{
						ob_start();

						try
						{
							/** @var PageRegular $objHandler */
							$objHandler->generate($objPage, true);
							$objResponse = new Response(ob_get_contents(), http_response_code());
						}
						finally
						{
							ob_end_clean();
						}

						return $objResponse;
					}

					/** @var PageRegular $objHandler */
					return $objHandler->getResponse($objPage, true);
			}
		}

		// Render the error page (see #5570)
		catch (\UnusedArgumentsException $e)
		{
			// Restore the globals (see #7659)
			list(
				$GLOBALS['TL_HEAD'],
				$GLOBALS['TL_BODY'],
				$GLOBALS['TL_MOOTOOLS'],
				$GLOBALS['TL_JQUERY'],
				$GLOBALS['TL_USER_CSS'],
				$GLOBALS['TL_FRAMEWORK_CSS']
			) = $arrBackup;

			/** @var PageError404 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();

			return $objHandler->getResponse();
		}
	}

	/**
	 * Try to load the page from the cache
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use the kernel.request event instead.
	 */
	protected function outputFromCache()
	{
		@trigger_error('Using FrontendIndex::outputFromCache() has been deprecated and will no longer work in Contao 5.0. Use the kernel.request event instead.', E_USER_DEPRECATED);
	}
}

class_alias(FrontendIndex::class, 'FrontendIndex');
