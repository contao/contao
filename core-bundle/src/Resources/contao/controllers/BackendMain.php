<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Main back end controller.
 */
class BackendMain extends Backend
{
	/**
	 * @var Template
	 *
	 * @todo Remove in Contao 5.0
	 */
	protected $Template;

	/**
	 * Current Ajax object
	 * @var Ajax
	 */
	protected $objAjax;

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
			$this->redirect($container->get('router')->generate('contao_backend_password'));
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
		$version = ContaoCoreBundle::getVersion();

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

			Controller::redirect(preg_replace('/(&(amp;)?|\?)mtg=[^& ]*$|mtg=[^&]*&(amp;)?/i', '', Environment::get('request')));
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
		$this->Template->setData($this->compileTemplateData($this->Template->getData()));

		return $this->Template->getResponse();
	}

	/**
	 * @internal
	 */
	protected function compileTemplateData(array $data): array
	{
		// Default headline
		if (!isset($data['headline']))
		{
			$data['headline'] = $GLOBALS['TL_LANG']['MSC']['dashboard'];
		}

		// Default title
		if (!isset($data['title']))
		{
			$data['title'] = $this->Template->headline;
		}

		$container = System::getContainer();
		$objSession = $container->get('session');

		// File picker reference (backwards compatibility)
		if (Input::get('popup') && Input::get('act') != 'show' && $objSession->get('filePickerRef') && ((Input::get('do') == 'page' && System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'page')) || (Input::get('do') == 'files' && System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'files'))))
		{
			$data['managerHref'] = StringUtil::ampersand($objSession->get('filePickerRef'));
			$data['manager'] = (strpos($objSession->get('filePickerRef'), 'contao/page?') !== false) ? $GLOBALS['TL_LANG']['MSC']['pagePickerHome'] : $GLOBALS['TL_LANG']['MSC']['filePickerHome'];
		}

		$data['theme'] = Backend::getTheme();
		$data['base'] = Environment::get('base');
		$data['language'] = $GLOBALS['TL_LANGUAGE'];
		$data['title'] = StringUtil::specialchars(strip_tags($data['title'] ?? ''));
		$data['host'] = Backend::getDecodedHostname();
		$data['charset'] = System::getContainer()->getParameter('kernel.charset');
		$data['home'] = $GLOBALS['TL_LANG']['MSC']['home'];
		$data['isPopup'] = Input::get('popup');
		$data['learnMore'] = sprintf($GLOBALS['TL_LANG']['MSC']['learnMore'], '<a href="https://contao.org" target="_blank" rel="noreferrer noopener">contao.org</a>');

		$twig = $container->get('twig');

		$data['menu'] = $twig->render('@ContaoCore/Backend/be_menu.html.twig');
		$data['headerMenu'] = $twig->render('@ContaoCore/Backend/be_header_menu.html.twig');

		return $data;
	}
}

class_alias(BackendMain::class, 'BackendMain');
