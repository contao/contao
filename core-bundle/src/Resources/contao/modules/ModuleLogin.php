<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Patchwork\Utf8;


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
	protected $strFlashType = 'contao.' . TL_MODE . '.error';


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

		// Set the last page visited
		if (!$_POST && $this->redirectBack)
		{
			$_SESSION['LAST_PAGE_VISITED'] = $this->getReferer();
		}

		// Login
		if (\Input::post('FORM_SUBMIT') == 'tl_login_' . $this->id)
		{
			// Check whether username and password are set
			if (empty($_POST['username']) || empty($_POST['password']))
			{
				\System::getContainer()->get('session')->getFlashBag()->set($this->strFlashType, $GLOBALS['TL_LANG']['MSC']['emptyField']);
				$this->reload();
			}

			$this->import('FrontendUser', 'User');
			$strRedirect = \Environment::get('request');

			// Redirect to the last page visited
			if ($this->redirectBack && $_SESSION['LAST_PAGE_VISITED'] != '')
			{
				$strRedirect = $_SESSION['LAST_PAGE_VISITED'];
			}
			else
			{
				// Redirect to the jumpTo page
				if ($this->jumpTo && ($objTarget = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					$strRedirect = $objTarget->getFrontendUrl();
				}

				// Overwrite the jumpTo page with an individual group setting
				$objMember = \MemberModel::findByUsername(\Input::post('username'));

				if ($objMember !== null)
				{
					$arrGroups = \StringUtil::deserialize($objMember->groups);

					if (!empty($arrGroups) && is_array($arrGroups))
					{
						$objGroupPage = \PageModel::findFirstActiveByMemberGroups($arrGroups);

						if ($objGroupPage !== null)
						{
							$strRedirect = $objGroupPage->getFrontendUrl();
						}
					}
				}
			}

			// Auto login is not allowed
			if (isset($_POST['autologin']) && !$this->autologin)
			{
				unset($_POST['autologin']);
				\Input::setPost('autologin', null);
			}

			// Login and redirect
			if ($this->User->login())
			{
				$this->redirect($strRedirect);
			}

			$this->reload();
		}

		// Logout and redirect to the website root if the current page is protected
		if (\Input::post('FORM_SUBMIT') == 'tl_logout_' . $this->id)
		{
			/** @var PageModel $objPage */
			global $objPage;

			$this->import('FrontendUser', 'User');
			$strRedirect = \Environment::get('request');

			// Redirect to last page visited
			if ($this->redirectBack && strlen($_SESSION['LAST_PAGE_VISITED']))
			{
				$strRedirect = $_SESSION['LAST_PAGE_VISITED'];
			}

			// Redirect home if the page is protected
			elseif ($objPage->protected)
			{
				$strRedirect = \Environment::get('base');
			}

			// Logout and redirect
			if ($this->User->logout())
			{
				$this->redirect($strRedirect);
			}

			$this->reload();
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		// Show logout form
		if (FE_USER_LOGGED_IN)
		{
			$this->import('FrontendUser', 'User');

			$this->Template->logout = true;
			$this->Template->formId = 'tl_logout_' . $this->id;
			$this->Template->slabel = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['logout']);
			$this->Template->loggedInAs = sprintf($GLOBALS['TL_LANG']['MSC']['loggedInAs'], $this->User->username);
			$this->Template->action = ampersand(\Environment::get('indexFreeRequest'));

			if ($this->User->lastLogin > 0)
			{
				/** @var PageModel $objPage */
				global $objPage;

				$this->Template->lastLogin = sprintf($GLOBALS['TL_LANG']['MSC']['lastLogin'][1], \Date::parse($objPage->datimFormat, $this->User->lastLogin));
			}

			return;
		}

		$flashBag = \System::getContainer()->get('session')->getFlashBag();

		if ($flashBag->has($this->strFlashType))
		{
			$this->Template->hasError = true;
			$this->Template->message = $flashBag->get($this->strFlashType)[0];
		}

		$this->Template->username = $GLOBALS['TL_LANG']['MSC']['username'];
		$this->Template->password = $GLOBALS['TL_LANG']['MSC']['password'][0];
		$this->Template->action = ampersand(\Environment::get('indexFreeRequest'));
		$this->Template->slabel = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['login']);
		$this->Template->value = \StringUtil::specialchars(\Input::post('username'));
		$this->Template->formId = 'tl_login_' . $this->id;
		$this->Template->autologin = ($this->autologin && \Config::get('autologin') > 0);
		$this->Template->autoLabel = $GLOBALS['TL_LANG']['MSC']['autologin'];
	}
}
