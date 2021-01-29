<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\BackendTheme\BackendThemes;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Util\PackageUtil;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
			trigger_deprecation('contao/core-bundle', '4.0', 'Using the "feRedirect" parameter has been deprecated and will no longer work in Contao 5.0. Use the "contao_backend_preview" route directly instead.');

			$this->redirectToFrontendPage(Input::get('page'), Input::get('article'));
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
		$version = PackageUtil::getContaoVersion();

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

		// Toggle nodes
		if (Input::get('mtg'))
		{
			/** @var AttributeBagInterface $objSessionBag */
			$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');
			$session = $objSessionBag->all();
			$session['backend_modules'][Input::get('mtg')] = (isset($session['backend_modules'][Input::get('mtg')]) && $session['backend_modules'][Input::get('mtg')] == 0) ? 1 : 0;
			$objSessionBag->replace($session);

			Controller::redirect(preg_replace('/(&(amp;)?|\?)mtg=[^& ]*/i', '', Environment::get('request')));
		}
		// Error
		elseif (Input::get('act') == 'error')
		{
			$this->Template->error = $GLOBALS['TL_LANG']['ERR']['general'];
			$this->Template->title = $GLOBALS['TL_LANG']['ERR']['general'];

			trigger_deprecation('contao/core-bundle', '4.0', 'Using "act=error" has been deprecated and will no longer work in Contao 5.0. Throw an exception instead.');
		}
		// Welcome screen
		elseif (!Input::get('do') && !Input::get('act'))
		{
			$this->Template->main .= $this->welcomeScreen();
			$this->Template->title = $GLOBALS['TL_LANG']['MSC']['dashboard'];
		}
		// Open a module
		elseif (Input::get('do'))
		{
			$picker = null;

			if (isset($_GET['picker']))
			{
				$picker = System::getContainer()->get('contao.picker.builder')->createFromData(Input::get('picker', true));

				if ($picker !== null && ($menu = $picker->getMenu()))
				{
					$this->Template->pickerMenu = System::getContainer()->get('contao.menu.renderer')->render($menu);
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
		if (!$this->Template->headline)
		{
			$this->Template->headline = $GLOBALS['TL_LANG']['MSC']['dashboard'];
		}

		// Default title
		if (!$this->Template->title)
		{
			$this->Template->title = $this->Template->headline;
		}

		$container = System::getContainer();
		$objSession = $container->get('session');

		// File picker reference (backwards compatibility)
		if (Input::get('popup') && Input::get('act') != 'show' && $objSession->get('filePickerRef') && ((Input::get('do') == 'page' && $this->User->hasAccess('page', 'modules')) || (Input::get('do') == 'files' && $this->User->hasAccess('files', 'modules'))))
		{
			$this->Template->managerHref = StringUtil::ampersand($objSession->get('filePickerRef'));
			$this->Template->manager = (strpos($objSession->get('filePickerRef'), 'contao/page?') !== false) ? $GLOBALS['TL_LANG']['MSC']['pagePickerHome'] : $GLOBALS['TL_LANG']['MSC']['filePickerHome'];
		}

		$themeName = Config::get('backendTheme') ?: Backend::getTheme();

		$this->Template->theme = $themeName;
		$this->Template->base = Environment::get('base');
		$this->Template->language = $GLOBALS['TL_LANGUAGE'];
		$this->Template->title = StringUtil::specialchars(strip_tags($this->Template->title));
		$this->Template->host = Backend::getDecodedHostname();
		$this->Template->charset = Config::get('characterSet');
		$this->Template->home = $GLOBALS['TL_LANG']['MSC']['home'];
		$this->Template->isPopup = Input::get('popup');
		$this->Template->learnMore = sprintf($GLOBALS['TL_LANG']['MSC']['learnMore'], '<a href="https://contao.org" target="_blank" rel="noreferrer noopener">contao.org</a>');

		$twig = $container->get('twig');

		$this->Template->menu = $twig->render('@ContaoCore/Backend/be_menu.html.twig');
		$this->Template->headerMenu = $twig->render('@ContaoCore/Backend/be_header_menu.html.twig');

		$this->Template->localeString = $this->Template->getLocaleString();
		$this->Template->dateString = $this->Template->getDateString();

		$backendThemes = $container->get(BackendThemes::class);

		if ('flexible' !== $themeName && null === $theme = $backendThemes->getTheme($themeName))
		{
			// Legacy theme detected.
			return $this->Template->getResponse();
		}

		return $this->Template->getResponse()->setContent(
			$twig->render('@ContaoCore/Backend/Layout/main.html.twig', $this->Template->getData())
		);
	}
}

class_alias(BackendMain::class, 'BackendMain');
