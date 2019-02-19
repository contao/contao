<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use ParagonIE\ConstantTime\Base32;
use Patchwork\Utf8;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Front end module "frontend two-factor".
 *
 * @author David Greminger <https://github.com/bytehead>
 */
class ModuleFrontendTwoFactor extends Module
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_frontend_two_factor';

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

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$container = System::getContainer();

		/** @var Router $router */
		$router = $container->get('router');

		/** @var PageModel $objPage */
		global $objPage;

		$user = FrontendUser::getInstance();

		$redirectPage = PageModel::findByPk($this->jumpTo);
		$return = $redirectPage instanceof PageModel ? $redirectPage->getAbsoluteUrl() : $objPage->getAbsoluteUrl();

		$this->Template->error = false;
		$this->Template->action = '';
		$this->Template->enforceTwoFactor = $objPage->enforceTwoFactor;
		$this->Template->targetPath = $return;

		if ($objPage->enforceTwoFactor)
		{
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['twoFactorEnforced'];
		}

		// Inform the user if 2FA is enforced
		if (!Input::get('2fa') && !$user->useTwoFactor && $objPage->enforceTwoFactor)
		{
			$this->enableTwoFactor($user, $return);
		}

		if (Input::get('2fa') == 'enable')
		{
			$this->enableTwoFactor($user, $return);
		}

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_disable')
		{
			$this->disableTwoFactor($user);
		}

		$this->Template->href = $router->generate('tl_page.'.$objPage->id, ['2fa' => 'enable']);
		$this->Template->isEnabled = $user->useTwoFactor;
		$this->Template->twoFactor = $GLOBALS['TL_LANG']['MSC']['twoFactorAuthentication'];
		$this->Template->explain = $GLOBALS['TL_LANG']['MSC']['twoFactorExplain'];
		$this->Template->active = $GLOBALS['TL_LANG']['MSC']['twoFactorActive'];
		$this->Template->enableButton = $GLOBALS['TL_LANG']['MSC']['enable'];
		$this->Template->disableButton = $GLOBALS['TL_LANG']['MSC']['disable'];
	}


	/**
	 * Enable two-factor authentication
	 *
	 * @param FrontendUser $user
	 * @param string       $return
	 */
	protected function enableTwoFactor(FrontendUser $user, $return)
	{
		// Return if 2FA is enabled already
		if ($user->useTwoFactor)
		{
			return;
		}

		$container = System::getContainer();

		/** @var Authenticator $authenticator */
		$authenticator = $container->get('contao.security.two_factor.authenticator');

		/** @var AuthenticationException|null $exception */
		$exception = $container->get('security.authentication_utils')->getLastAuthenticationError();

		if (($exception instanceof InvalidTwoFactorCodeException))
		{
			$this->Template->error = true;
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['invalidTwoFactor'];
		}

		// Validate the verification code
		if (Input::post('FORM_SUBMIT') == 'tl_two_factor')
		{
			if ($authenticator->validateCode($user, Input::post('verify')))
			{
				// Enable 2FA
				$user->useTwoFactor = '1';
				$user->save();

				throw new RedirectResponseException($return);
			}

			$this->Template->error = true;
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['invalidTwoFactor'];
		}

		// Generate the secret
		if (!$user->secret)
		{
			$user->secret = random_bytes(128);
			$user->save();
		}

		/** @var Request $request */
		$request = $container->get('request_stack')->getCurrentRequest();

		$this->Template->enable = true;
		$this->Template->secret = Base32::encodeUpperUnpadded($user->secret);
		$this->Template->textCode = $GLOBALS['TL_LANG']['MSC']['twoFactorTextCode'];
		$this->Template->qrCode = base64_encode($authenticator->getQrCode($user, $request));
		$this->Template->scan = $GLOBALS['TL_LANG']['MSC']['twoFactorScan'];
		$this->Template->verify = $GLOBALS['TL_LANG']['MSC']['twoFactorVerification'];
		$this->Template->verifyHelp = $GLOBALS['TL_LANG']['MSC']['twoFactorVerificationHelp'];
	}

	/**
	 * Disable two-factor authentication
	 *
	 * @param FrontendUser $user
	 * @param string       $return
	 */
	protected function disableTwoFactor(FrontendUser $user)
	{
		// Return if 2FA is disabled already
		if (!$user->useTwoFactor)
		{
			return;
		}

		/** @var PageModel $objPage */
		global $objPage;

		$user->secret = null;
		$user->useTwoFactor = '';
		$user->save();


		throw new RedirectResponseException($objPage->getAbsoluteUrl());
	}
}

class_alias(ModuleFrontendTwoFactor::class, 'ModuleFrontendTwoFactor');
