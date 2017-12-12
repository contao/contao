<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Patchwork\Utf8;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;


/**
 * Front end module "login".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleLogin extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_login';

	/**
	 * Flash type
	 * @var string
	 */
	protected $strFlashType = 'contao.FE.error';


	/**
	 * Display a login form
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['login'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		/** @var Request $request */
		$request = \System::getContainer()->get('request_stack')->getCurrentRequest();

		// Set the last page visited (see #8632)
		if ($this->redirectBack && !$request->isMethod(Request::METHOD_POST) && ($strReferer = $this->getReferer()) != \Environment::get('request'))
		{
			$session = \System::getContainer()->get('session');
			$session->set('LAST_PAGE_VISITED', $strReferer);
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var RouterInterface $router */
		$router = \System::getContainer()->get('router');

		/** @var TokenInterface $token */
		$token = \System::getContainer()->get('security.token_storage')->getToken();

		// Do not redirect if authentication is successful
		if ($token !== null && $token->getUser() instanceof FrontendUser && $token->isAuthenticated())
		{
			$this->import('FrontendUser', 'User');

			$this->Template->logout = true;
			$this->Template->formId = 'tl_logout_' . $this->id;
			$this->Template->slabel = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['logout']);
			$this->Template->loggedInAs = sprintf($GLOBALS['TL_LANG']['MSC']['loggedInAs'], $this->User->username);
			$this->Template->action = $router->generate('contao_frontend_logout');
			$this->Template->targetPath = null;

			if ($this->User->lastLogin > 0)
			{
				/** @var PageModel $objPage */
				global $objPage;

				$this->Template->lastLogin = sprintf($GLOBALS['TL_LANG']['MSC']['lastLogin'][1], \Date::parse($objPage->datimFormat, $this->User->lastLogin));
			}

			return;
		}

		if (\System::getContainer()->get('session')->isStarted())
		{
			/** @var FlashBagInterface $flashBag */
			$flashBag = \System::getContainer()->get('session')->getFlashBag();

			if ($flashBag->has($this->strFlashType))
			{
				$this->Template->hasError = true;
				$this->Template->message = $flashBag->get($this->strFlashType)[0];
			}
		}

		/** @var Request $request */
		$request = \System::getContainer()->get('request_stack')->getCurrentRequest();

		$this->Template->targetName = '_target_path';
		$this->Template->targetPath = $request->getRequestUri();

		/** @var Session $session */
		$session = \System::getContainer()->get('session');

		// Redirect to the last page visited
		if ($this->redirectBack && $session->get('LAST_PAGE_VISITED'))
		{
			$this->Template->targetName = '_target_referer';
			$this->Template->targetPath = $session->get('LAST_PAGE_VISITED');
		}
		elseif ($this->jumpTo && ($objTarget = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$this->Template->targetPath = $objTarget->getAbsoluteUrl();
		}

		$this->Template->username = $GLOBALS['TL_LANG']['MSC']['username'];
		$this->Template->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$this->Template->action = $router->generate('contao_frontend_login');
		$this->Template->slabel = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['login']);
		$this->Template->value = \StringUtil::specialchars($session->get(Security::LAST_USERNAME));
		$this->Template->formId = 'tl_login_' . $this->id;
		$this->Template->autologin = ($this->autologin && \Config::get('autologin') > 0);
		$this->Template->autoLabel = $GLOBALS['TL_LANG']['MSC']['autologin'];
	}
}
