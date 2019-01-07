<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Security\Exception\LockedException;
use Patchwork\Utf8;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Front end module "login".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleLogin extends Module
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
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['login'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		if (!$_POST && $this->redirectBack && ($strReferer = $this->getReferer()) != Environment::get('request'))
		{
			$_SESSION['LAST_PAGE_VISITED'] = $strReferer;
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$container = System::getContainer();

		/** @var RouterInterface $router */
		$router = $container->get('router');

		if ($container->get('contao.security.token_checker')->hasFrontendUser())
		{
			/** @var PageModel $objPage */
			global $objPage;

			$this->import(FrontendUser::class, 'User');

			$strRedirect = Environment::get('base').Environment::get('request');

			// Redirect to last page visited
			if ($this->redirectBack && $_SESSION['LAST_PAGE_VISITED'] != '')
			{
				$strRedirect = Environment::get('base').$_SESSION['LAST_PAGE_VISITED'];
			}

			// Redirect home if the page is protected
			elseif ($objPage->protected)
			{
				$strRedirect = Environment::get('base');
			}

			$this->Template->logout = true;
			$this->Template->formId = 'tl_logout_' . $this->id;
			$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['logout']);
			$this->Template->loggedInAs = sprintf($GLOBALS['TL_LANG']['MSC']['loggedInAs'], $this->User->username);
			$this->Template->action = $container->get('security.logout_url_generator')->getLogoutPath();
			$this->Template->targetPath = StringUtil::specialchars($strRedirect);

			if ($this->User->lastLogin > 0)
			{
				$this->Template->lastLogin = sprintf($GLOBALS['TL_LANG']['MSC']['lastLogin'][1], Date::parse($objPage->datimFormat, $this->User->lastLogin));
			}

			return;
		}

		$exception = $container->get('security.authentication_utils')->getLastAuthenticationError();

		if ($exception instanceof LockedException)
		{
			$this->Template->hasError = true;
			$this->Template->message = sprintf($GLOBALS['TL_LANG']['ERR']['accountLocked'], $exception->getLockedMinutes());
		}
		elseif ($exception instanceof AuthenticationException)
		{
			$this->Template->hasError = true;
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['invalidLogin'];
		}

		$blnRedirectBack = false;
		$strRedirect = Environment::get('base').Environment::get('request');

		// Redirect to the last page visited
		if ($this->redirectBack && $_SESSION['LAST_PAGE_VISITED'] != '')
		{
			$blnRedirectBack = true;
			$strRedirect = $_SESSION['LAST_PAGE_VISITED'];
		}

		// Redirect to the jumpTo page
		elseif ($this->jumpTo && ($objTarget = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$strRedirect = $objTarget->getAbsoluteUrl();
		}

		$this->Template->username = $GLOBALS['TL_LANG']['MSC']['username'];
		$this->Template->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$this->Template->action = $router->generate('contao_frontend_login');
		$this->Template->slabel = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['login']);
		$this->Template->value = StringUtil::specialchars($container->get('security.authentication_utils')->getLastUsername());
		$this->Template->formId = 'tl_login_' . $this->id;
		$this->Template->autologin = $this->autologin;
		$this->Template->autoLabel = $GLOBALS['TL_LANG']['MSC']['autologin'];
		$this->Template->forceTargetPath = (int) $blnRedirectBack;
		$this->Template->targetPath = StringUtil::specialchars($strRedirect);
		$this->Template->failurePath = StringUtil::specialchars(Environment::get('base').Environment::get('request'));
	}
}

class_alias(ModuleLogin::class, 'ModuleLogin');
