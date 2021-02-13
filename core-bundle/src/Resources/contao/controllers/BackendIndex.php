<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\Backend\BackendLoginController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

trigger_deprecation('contao/core-bundle', '4.12', 'Using the "Contao\BackendIndex" class has been deprecated and will no longer work in Contao 5.0.');

/**
 * Handle back end logins and logouts.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated this controller was moved to the \Contao\CoreBundle\Controller\Backend namespace
 */
class BackendIndex extends Backend
{
	/**
	 * Initialize the controller
	 *
	 * 1. Import the user
	 * 2. Call the parent constructor
	 * 3. Login the user
	 * 4. Load the language files
	 * DO NOT CHANGE THIS ORDER!
	 */
	public function __construct()
	{
		$this->import(BackendUser::class, 'User');
		parent::__construct();

		System::loadLanguageFile('default');
		System::loadLanguageFile('tl_user');
	}

	/**
	 * Run the controller and parse the login template
	 *
	 * @return Response
	 */
	public function run()
	{
		$container = System::getContainer();

		$request = $container->get('request_stack')->getCurrentRequest();
		$path['_controller'] = BackendLoginController::class;
		$subRequest = $request->duplicate($query, null, $path);

		return $container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
	}
}

class_alias(BackendIndex::class, 'BackendIndex');
