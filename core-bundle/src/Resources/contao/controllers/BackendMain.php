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
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\HttpKernel\JwtManager;
use Contao\CoreBundle\Util\PackageUtil;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;

/**
 * Main back end controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BackendMain extends Backend
{

	/**
	 * Current Ajax object
	 * @var Ajax
	 */
	protected $objAjax;

	/**
	 * @var BackendTemplate
	 */
	protected $Template;

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
		$container = System::getContainer();

		/** @var AuthorizationCheckerInterface $authorizationChecker */
		$authorizationChecker = $container->get('security.authorization_checker');

		$this->import(BackendUser::class, 'User');
		parent::__construct();

		if (!$authorizationChecker->isGranted('ROLE_USER'))
		{
			throw new AccessDeniedException('Access denied');
		}

		// Password change required
		if ($this->User->pwChange && !$authorizationChecker->isGranted('ROLE_PREVIOUS_ADMIN'))
		{
			$this->redirect('contao/password.php');
		}

		// Two-factor setup required
		if (!$this->User->useTwoFactor && $container->getParameter('contao.security.two_factor.enforce_backend') && Input::get('do') != 'security')
		{
			$this->redirect($container->get('router')->generate('contao_backend', array('do'=>'security')));
		}

		// Front end redirect
		if (Input::get('do') == 'feRedirect')
		{
			$this->redirectToFrontendPage(Input::get('page'), Input::get('article'));
		}

		// Debug redirect
		if ($this->User->isAdmin && Input::get('do') == 'debug')
		{
			$objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

			if ($objRequest === null)
			{
				throw new \RuntimeException('The request stack did not contain a request');
			}

			$objJwtManager = $objRequest->attributes->get(JwtManager::ATTRIBUTE);

			if (!$objJwtManager instanceof JwtManager)
			{
				if (($qs = $objRequest->getQueryString()) !== null)
				{
					$qs = '?' . $qs;
				}

				$this->redirect('/preview.php' . $objRequest->getPathInfo() . $qs);
			}

			$strReferer = Input::get('referer') ? '?' . base64_decode(Input::get('referer', true)) : '';
			$objResponse = new RedirectResponse('/preview.php' . $objRequest->getPathInfo() . $strReferer);

			if (Input::get('enable') != $container->get('kernel')->isDebug())
			{
				$objJwtManager->addResponseCookie($objResponse, array('debug' => (bool) Input::get('enable')));
			}

			throw new ResponseException($objResponse);
		}

		// Backend user profile redirect
		if (Input::get('do') == 'login' && (Input::get('act') != 'edit' && Input::get('id') != $this->User->id))
		{
			$strUrl = $container->get('router')->generate('contao_backend', array
			(
				'do' => 'login',
				'act' => 'edit',
				'id' => $this->User->id,
				'ref' => $container->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id'),
				'rt' => REQUEST_TOKEN,
			));

			$this->redirect($strUrl);
		}

		System::loadLanguageFile('default');
		System::loadLanguageFile('modules');
	}

	/**
	 * Run the controller and parse the login template
	 *
	 * @return Response
	 */
	public function run()
	{
		try
		{
			$version = PackageUtil::getVersion('contao/core-bundle');
		}
		catch (\OutOfBoundsException $e)
		{
			$version = PackageUtil::getVersion('contao/contao');
		}

		$this->Template = new BackendTemplate('be_main');
		$this->Template->version = $version;

		if (isset($GLOBALS['TL_LANG']['MSC']['version']))
		{
			$this->Template->version = $GLOBALS['TL_LANG']['MSC']['version'] . ' ' . $version;
		}

		$this->Template->main = '';

		// Ajax request
		if ($_POST && Environment::get('isAjaxRequest'))
		{
			$this->objAjax = new Ajax(Input::post('action'));
			$this->objAjax->executePreActions();
		}

		// Error
		if (Input::get('act') == 'error')
		{
			$this->Template->error = $GLOBALS['TL_LANG']['ERR']['general'];
			$this->Template->title = $GLOBALS['TL_LANG']['ERR']['general'];

			@trigger_error('Using act=error has been deprecated and will no longer work in Contao 5.0. Throw an exception instead.', E_USER_DEPRECATED);
		}
		// Welcome screen
		elseif (!Input::get('do') && !Input::get('act'))
		{
			$this->Template->main .= $this->welcomeScreen();
			$this->Template->title = $GLOBALS['TL_LANG']['MSC']['home'];
		}
		// Open a module
		elseif (Input::get('do'))
		{
			$picker = null;

			if (isset($_GET['picker']))
			{
				$picker = System::getContainer()->get('contao.picker.builder')->createFromData(Input::get('picker', true));

				if ($picker !== null)
				{
					if (($menu = $picker->getMenu()) && $menu->count() > 1)
					{
						$this->Template->pickerMenu = System::getContainer()->get('contao.menu.renderer')->render($menu);
					}
				}
			}

			$this->Template->main .= $this->getBackendModule(Input::get('do'), $picker);
			$this->Template->title = $this->Template->headline;
		}

		return $this->output();
	}

	/**
	 * Add the welcome screen
	 *
	 * @return string
	 */
	protected function welcomeScreen()
	{
		System::loadLanguageFile('explain');

		$objTemplate = new BackendTemplate('be_welcome');
		$objTemplate->messages = Message::generateUnwrapped() . Backend::getSystemMessages();
		$objTemplate->loginMsg = $GLOBALS['TL_LANG']['MSC']['firstLogin'];

		// Add the login message
		if ($this->User->lastLogin > 0)
		{
			$formatter = new DateTimeFormatter(System::getContainer()->get('translator'));
			$diff = $formatter->formatDiff(new \DateTime(date('Y-m-d H:i:s', $this->User->lastLogin)), new \DateTime());

			$objTemplate->loginMsg = sprintf(
				$GLOBALS['TL_LANG']['MSC']['lastLogin'][1],
				'<time title="' . Date::parse(Config::get('datimFormat'), $this->User->lastLogin) . '">' . $diff . '</time>'
			);
		}

		// Add the versions overview
		Versions::addToTemplate($objTemplate);

		$objTemplate->showDifferences = StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['showDifferences']));
		$objTemplate->recordOfTable = StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['recordOfTable']));
		$objTemplate->systemMessages = $GLOBALS['TL_LANG']['MSC']['systemMessages'];
		$objTemplate->shortcuts = $GLOBALS['TL_LANG']['MSC']['shortcuts'][0];
		$objTemplate->shortcutsLink = $GLOBALS['TL_LANG']['MSC']['shortcuts'][1];
		$objTemplate->editElement = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['editElement']);

		return $objTemplate->parse();
	}

	/**
	 * Output the template file
	 *
	 * @return Response
	 */
	protected function output()
	{
		// Default headline
		if ($this->Template->headline == '')
		{
			$this->Template->headline = $GLOBALS['TL_LANG']['MSC']['dashboard'];
		}

		// Default title
		if ($this->Template->title == '')
		{
			$this->Template->title = $this->Template->headline;
		}

		$container = System::getContainer();
		$objSession = $container->get('session');

		// File picker reference (backwards compatibility)
		if (Input::get('popup') && Input::get('act') != 'show' && ((Input::get('do') == 'page' && $this->User->hasAccess('page', 'modules')) || (Input::get('do') == 'files' && $this->User->hasAccess('files', 'modules'))) && $objSession->get('filePickerRef'))
		{
			$this->Template->managerHref = ampersand($objSession->get('filePickerRef'));
			$this->Template->manager = (strpos($objSession->get('filePickerRef'), 'contao/page?') !== false) ? $GLOBALS['TL_LANG']['MSC']['pagePickerHome'] : $GLOBALS['TL_LANG']['MSC']['filePickerHome'];
		}

		$referer = null;

		if ($request = $container->get('request_stack')->getCurrentRequest())
		{
			$referer = base64_encode($request->getQueryString());
		}

		$this->Template->theme = Backend::getTheme();
		$this->Template->base = Environment::get('base');
		$this->Template->language = $GLOBALS['TL_LANGUAGE'];
		$this->Template->title = StringUtil::specialchars(strip_tags($this->Template->title));
		$this->Template->charset = Config::get('characterSet');
		$this->Template->account = $GLOBALS['TL_LANG']['MOD']['login'][1];
		$this->Template->preview = $GLOBALS['TL_LANG']['MSC']['fePreview'];
		$this->Template->previewTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['fePreviewTitle']);
		$this->Template->profile = $GLOBALS['TL_LANG']['MSC']['profile'];
		$this->Template->canDebug = $this->User->isAdmin;
		$this->Template->isDebug = $container->get('kernel')->isDebug();
		$this->Template->debug = $container->get('kernel')->isDebug() ? $GLOBALS['TL_LANG']['MSC']['disableDebugMode'] : $GLOBALS['TL_LANG']['MSC']['enableDebugMode'];
		$this->Template->referer = $referer;
		$this->Template->profileTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['profileTitle']);
		$this->Template->security = $GLOBALS['TL_LANG']['MSC']['security'];
		$this->Template->pageOffset = (int) Input::cookie('BE_PAGE_OFFSET');
		$this->Template->logout = $GLOBALS['TL_LANG']['MSC']['logoutBT'];
		$this->Template->logoutLink = System::getContainer()->get('security.logout_url_generator')->getLogoutUrl();
		$this->Template->logoutTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['logoutBTTitle']);
		$this->Template->user = $this->User;
		$this->Template->username = $GLOBALS['TL_LANG']['MSC']['user'] . ' ' . $this->User->getUsername();
		$this->Template->request = ampersand(Environment::get('request'));
		$this->Template->top = $GLOBALS['TL_LANG']['MSC']['backToTop'];
		$this->Template->modules = $this->User->navigation();
		$this->Template->home = $GLOBALS['TL_LANG']['MSC']['home'];
		$this->Template->homeTitle = $GLOBALS['TL_LANG']['MSC']['homeTitle'];
		$this->Template->backToTop = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backToTopTitle']);
		$this->Template->expandNode = $GLOBALS['TL_LANG']['MSC']['expandNode'];
		$this->Template->collapseNode = $GLOBALS['TL_LANG']['MSC']['collapseNode'];
		$this->Template->loadingData = $GLOBALS['TL_LANG']['MSC']['loadingData'];
		$this->Template->isPopup = Input::get('popup');
		$this->Template->systemMessages = $GLOBALS['TL_LANG']['MSC']['systemMessages'];
		$this->Template->burger = $GLOBALS['TL_LANG']['MSC']['burgerTitle'];
		$this->Template->learnMore = sprintf($GLOBALS['TL_LANG']['MSC']['learnMore'], '<a href="https://contao.org" target="_blank" rel="noreferrer noopener">contao.org</a>');
		$this->Template->ref = $container->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id');
		$this->Template->menu = $container->get('contao.menu.backend_menu_renderer')->render($container->get('contao.menu.backend_menu_builder')->create());
		$this->Template->headerNavigation = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['headerNavigation']);

		$strSystemMessages = Backend::getSystemMessages();
		$this->Template->systemMessagesCount = substr_count($strSystemMessages, 'class="tl_');
		$this->Template->systemErrorMessagesCount = substr_count($strSystemMessages, 'class="tl_error"');

		$this->setImpersonatedLogout();

		// Front end preview links
		if (\defined('CURRENT_ID') && CURRENT_ID != '')
		{
			if (Input::get('do') == 'page')
			{
				$this->Template->frontendFile = '?page=' . CURRENT_ID;
			}
			elseif (Input::get('do') == 'article' && ($objArticle = ArticleModel::findByPk(CURRENT_ID)) !== null)
			{
				$this->Template->frontendFile = '?page=' . $objArticle->pid;
			}
			elseif (Input::get('do') != '')
			{
				$event = new PreviewUrlCreateEvent(Input::get('do'), CURRENT_ID);
				$container->get('event_dispatcher')->dispatch(ContaoCoreEvents::PREVIEW_URL_CREATE, $event);

				if (($strQuery = $event->getQuery()) !== null)
				{
					$this->Template->frontendFile = '?' . $strQuery;
				}
			}
		}

		return $this->Template->getResponse();
	}

	/**
	 * Adjusts the logout link if the current user is impersonated.
	 *
	 * @throws \RuntimeException
	 */
	private function setImpersonatedLogout()
	{
		$token = System::getContainer()->get('security.token_storage')->getToken();

		if (!$token instanceof TokenInterface)
		{
			return;
		}

		$impersonatorUser = null;

		foreach ($token->getRoles() as $role)
		{
			if ($role instanceof SwitchUserRole)
			{
				$impersonatorUser = $role->getSource()->getUsername();
				break;
			}
		}

		if (!$impersonatorUser)
		{
			return;
		}

		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request === null)
		{
			throw new \RuntimeException('The request stack did not contain a request');
		}

		$firewallMap = System::getContainer()->get('security.firewall.map');

		// Generate the "exit impersonation" path from the current request
		if (($firewallConfig = $firewallMap->getFirewallConfig($request)) === null || ($switchUserConfig = $firewallConfig->getSwitchUser()) === null)
		{
			return;
		}

		// Take the use back to the "users" module
		$arrParams = array('do' => 'user', urlencode($switchUserConfig['parameter']) => SwitchUserListener::EXIT_VALUE);

		$this->Template->logout = sprintf($GLOBALS['TL_LANG']['MSC']['switchBT'], $impersonatorUser);
		$this->Template->logoutLink = System::getContainer()->get('router')->generate('contao_backend', $arrParams);
	}
}

class_alias(BackendMain::class, 'BackendMain');
