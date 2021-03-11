<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\Backend\BackendMainController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Fragment\Reference\FragmentReference;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

trigger_deprecation('contao/core-bundle', '4.12', 'Using the "Contao\BackendMain" class has been deprecated and will no longer work in Contao 5.0.');

/**
 * Main back end controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated this controller was moved to the \Contao\CoreBundle\Controller\Backend namespace
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
		return $this->output();
	}

	/**
	 * Add the welcome screen
	 *
	 * @return string
	 */
	protected function welcomeScreen()
	{
		trigger_deprecation('contao/core-bundle', '4.12', 'Using BackendMain::welcomeScreen() has been deprecated.');

		$container = System::getContainer();
		$fragmentHandler = $container->get('fragment.handler');

		return $fragmentHandler->render(new FragmentReference('contao.dashboard_widget.welcome_screen'));
	}

	/**
	 * Output the template file
	 *
	 * @return Response
	 */
	protected function output()
	{
		$container = System::getContainer();

		$request = $container->get('request_stack')->getCurrentRequest();
		$path['_controller'] = BackendMainController::class;
		$subRequest = $request->duplicate($query, null, $path);

		return $container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
	}
}

class_alias(BackendMain::class, 'BackendMain');
