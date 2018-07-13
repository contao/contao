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
use Symfony\Component\HttpFoundation\Request;

/**
 * Back end module "two factor".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModuleTwoFactor extends BackendModule
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_two_factor';

	/**
	 * Generate the module
	 *
	 * @throws \Exception
	 */
	protected function compile()
	{
		$this->import('BackendUser', 'User');

		$container = System::getContainer();
		$strRefererId = $container->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id');
		$ref = $container->get('router')->generate('contao_backend', array('do'=>'two-factor', 'ref'=>$strRefererId));
		$user = BackendUser::getInstance();

		$this->Template->href = $this->getReferer(true);
		$this->Template->title = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
		$this->Template->button = $GLOBALS['TL_LANG']['MSC']['backBT'];
		$this->Template->ref = $ref;
		$this->Template->action = \Environment::get('indexFreeRequest');

		// Enable two-factor authentication
		if (Input::get('act') == 'enable')
		{
			$this->enableTwoFactor($user, $ref);
		}

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_disable')
		{
			$user->secret = null;
			$user->useTwoFactor = '';
			$user->save();

			throw new RedirectResponseException($ref);
		}

		$this->Template->isEnabled = $this->User->useTwoFactor;
		$this->Template->activation = $GLOBALS['TL_LANG']['MSC']['twoFactorActivation'];
		$this->Template->explain = $GLOBALS['TL_LANG']['MSC']['twoFactorExplain'];
		$this->Template->active = $GLOBALS['TL_LANG']['MSC']['twoFactorActive'];
		$this->Template->enableButton = $GLOBALS['TL_LANG']['MSC']['enable'];
		$this->Template->disableButton = $GLOBALS['TL_LANG']['MSC']['disable'];
	}

	/**
	 * Enable two-factor authentication
	 *
	 * @param BackendUser $user
	 * @param string      $ref
	 */
	protected function enableTwoFactor(BackendUser $user, $ref)
	{
		// Return if 2FA is enabled already
		if ($user->useTwoFactor)
		{
			return;
		}

		$container = System::getContainer();

		/** @var Authenticator $authenticator */
		$authenticator = $container->get('contao.security.two_factor.authenticator');

		// Validate the verification code
		if (Input::post('FORM_SUBMIT') == 'tl_two_factor')
		{
			if ($authenticator->validateCode($user, Input::post('verify')))
			{
				// Enable 2FA
				$user->useTwoFactor = '1';
				$user->save();

				throw new RedirectResponseException($ref);
			}

			$this->Template->error = true;
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
		$this->Template->qrCode = base64_encode($authenticator->getQrCode($user, $request));
		$this->Template->scan = $GLOBALS['TL_LANG']['MSC']['twoFactorScan'];
		$this->Template->verify = $GLOBALS['TL_LANG']['MSC']['twoFactorVerification'];
		$this->Template->verifyHelp = $GLOBALS['TL_LANG']['MSC']['twoFactorVerificationHelp'];
	}
}
