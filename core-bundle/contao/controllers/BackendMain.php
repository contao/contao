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
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\String\HtmlAttributes;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Main back end controller.
 */
class BackendMain extends Backend
{
	/**
	 * @var Template
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
		parent::__construct();

		$container = System::getContainer();
		$authorizationChecker = $container->get('security.authorization_checker');

		if (!$authorizationChecker->isGranted('ROLE_USER'))
		{
			throw new AccessDeniedException('Access denied');
		}

		$user = BackendUser::getInstance();

		// Password change required
		if ($user->pwChange && !$authorizationChecker->isGranted('ROLE_PREVIOUS_ADMIN'))
		{
			$this->redirect($container->get('router')->generate('contao_backend_password'));
		}

		// Two-factor setup required
		if (!$user->useTwoFactor && $container->getParameter('contao.security.two_factor.enforce_backend') && Input::get('do') != 'security')
		{
			$this->redirect($container->get('router')->generate('contao_backend', array('do'=>'security')));
		}

		// Backend user profile redirect
		if (Input::get('do') == 'login' && (Input::get('act') != 'edit' && Input::get('id') != $user->id))
		{
			$strUrl = $container->get('router')->generate('contao_backend', array
			(
				'do' => 'login',
				'act' => 'edit',
				'id' => $user->id,
				'ref' => $container->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id'),
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
		if (Input::post('action') && Environment::get('isAjaxRequest'))
		{
			$this->objAjax = new Ajax(Input::post('action'));
			$this->objAjax->executePreActions();
		}

		// Toggle nodes
		if (Input::get('mtg'))
		{
			$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
			$session = $objSessionBag->all();
			$session['backend_modules'][Input::get('mtg')] = (isset($session['backend_modules'][Input::get('mtg')]) && $session['backend_modules'][Input::get('mtg')] == 0) ? 1 : 0;
			$objSessionBag->replace($session);

			Controller::redirect(preg_replace('/(&(amp;)?|\?)mtg=[^& ]*$|mtg=[^&]*&(amp;)?/i', '', Environment::get('requestUri')));
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

			if (Input::get('picker') !== null)
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

		// Set the status code to 422 if a widget did not validate, so that
		// Turbo can handle form errors.
		$response = $this->output();

		if (System::getContainer()->get('request_stack')?->getMainRequest()->attributes->has('_contao_widget_error'))
		{
			$response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		return $response;
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

		$user = BackendUser::getInstance();

		// Add the login message
		if ($user->lastLogin > 0)
		{
			$formatter = new DateTimeFormatter(System::getContainer()->get('translator'));
			$diff = $formatter->formatDiff(new \DateTime(date('Y-m-d H:i:s', $user->lastLogin)), new \DateTime());

			$objTemplate->loginMsg = \sprintf(
				$GLOBALS['TL_LANG']['MSC']['lastLogin'][1],
				'<time title="' . Date::parse(Config::get('datimFormat'), $user->lastLogin) . '">' . $diff . '</time>'
			);
		}

		// Add the versions overview
		Versions::addToTemplate($objTemplate);

		$objTemplate->showDifferences = StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['showDifferences']));
		$objTemplate->recordOfTable = StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['recordOfTable']));
		$objTemplate->systemMessages = $GLOBALS['TL_LANG']['MSC']['systemMessages'];
		$objTemplate->shortcuts = $GLOBALS['TL_LANG']['MSC']['shortcuts'][0];
		$objTemplate->shortcutsLink = \sprintf($GLOBALS['TL_LANG']['MSC']['shortcuts'][1], 'https://to.contao.org/docs/shortcuts');
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

		$response = $this->Template->getResponse();

		if (Input::get('popup') !== null)
		{
			$response->headers->set('Content-Security-Policy', "frame-ancestors 'self'", false);
		}

		return $response;
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

		$data['theme'] = Backend::getTheme();
		$data['language'] = $GLOBALS['TL_LANGUAGE'];
		$data['title'] = StringUtil::specialchars(strip_tags($data['title'] ?? ''));
		$data['host'] = Backend::getDecodedHostname();
		$data['charset'] = System::getContainer()->getParameter('kernel.charset');
		$data['home'] = $GLOBALS['TL_LANG']['MSC']['home'];
		$data['isPopup'] = Input::get('popup');
		$data['learnMore'] = \sprintf($GLOBALS['TL_LANG']['MSC']['learnMore'], '<a href="https://contao.org" target="_blank" rel="noreferrer noopener">contao.org</a>');

		$twig = $container->get('twig');

		$data['menu'] = $twig->render('@ContaoCore/Backend/be_menu.html.twig');
		$data['headerMenu'] = $twig->render('@ContaoCore/Backend/be_header_menu.html.twig');

		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if ($responseContext?->has(HtmlHeadBag::class))
		{
			$htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
			$data['metaTags'] = array_combine(
				array_map(Contao\StringUtil::specialcharsAttribute(...), array_keys($htmlHeadBag->getMetaTags())),
				array_map(Contao\StringUtil::specialcharsAttribute(...), array_values($htmlHeadBag->getMetaTags()))
			);
		}

		if ($responseContext?->has(HtmlAttributes::class))
		{
			$data['rootAttributes'] = $responseContext->get(HtmlAttributes::class);
		}

		return $data;
	}
}
