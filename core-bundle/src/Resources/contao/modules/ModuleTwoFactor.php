<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Security\TwoFactor\BackupCode\BackupCodeManager;
use ParagonIE\ConstantTime\Base32;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

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
	 */
	protected function compile()
	{
		$container = System::getContainer();

		/** @var Security $security */
		$security = $container->get('security.helper');

		if (!$security->isGranted('IS_AUTHENTICATED_FULLY'))
		{
			throw new AccessDeniedException('User is not fully authenticated');
		}

		$user = BackendUser::getInstance();

		// Inform the user if 2FA is enforced
		if (!$user->useTwoFactor && empty($_GET['act']) && $container->getParameter('contao.security.two_factor.enforce_backend'))
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['twoFactorEnforced']);
		}

		$ref = $container->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id');
		$return = $container->get('router')->generate('contao_backend', array('do'=>'security', 'ref'=>$ref));

		$this->Template->href = $this->getReferer(true);
		$this->Template->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
		$this->Template->button = $GLOBALS['TL_LANG']['MSC']['backBT'];
		$this->Template->ref = $ref;
		$this->Template->action = Environment::get('indexFreeRequest');
		$this->Template->messages = Message::generateUnwrapped();

		if (Input::get('act') == 'enable')
		{
			$this->enableTwoFactor($user, $return);
		}

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_disable')
		{
			$this->disableTwoFactor($user, $return);
		}

		$showBackupCodes = false;

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_show_backup_codes')
		{
			$showBackupCodes = true;

			if (!$user->backupCodes || !\count(json_decode($user->backupCodes, true)))
			{
				$this->generateBackupCodes($user);
			}
		}

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_generate_backup_codes')
		{
			$showBackupCodes = true;
			$this->generateBackupCodes($user);
		}

		$this->Template->isEnabled = (bool) $user->useTwoFactor;
		$this->Template->twoFactor = $GLOBALS['TL_LANG']['MSC']['twoFactorAuthentication'];
		$this->Template->explain = $GLOBALS['TL_LANG']['MSC']['twoFactorExplain'];
		$this->Template->active = $GLOBALS['TL_LANG']['MSC']['twoFactorActive'];
		$this->Template->enableButton = $GLOBALS['TL_LANG']['MSC']['enable'];
		$this->Template->disableButton = $GLOBALS['TL_LANG']['MSC']['disable'];
		$this->Template->showBackupCodes = $showBackupCodes;
		$this->Template->backupCodesLabel = $GLOBALS['TL_LANG']['MSC']['twoFactorBackupCodesLabel'];
		$this->Template->backupCodesShow = $GLOBALS['TL_LANG']['MSC']['twoFactorBackupCodesShow'];
		$this->Template->backupCodesExplain = $GLOBALS['TL_LANG']['MSC']['twoFactorBackupCodesExplain'];
		$this->Template->backupCodesInfo = $GLOBALS['TL_LANG']['MSC']['twoFactorBackupCodesInfo'];
		$this->Template->backupCodesGenerate = $GLOBALS['TL_LANG']['MSC']['twoFactorBackupCodesGenerate'];
		$this->Template->backupCodesGenerateInfo = $GLOBALS['TL_LANG']['MSC']['twoFactorBackupCodesGenerateInfo'];
		$this->Template->backupCodes = json_decode((string) $user->backupCodes, true) ?? array();
	}

	/**
	 * Enable two-factor authentication
	 *
	 * @param BackendUser $user
	 * @param string      $return
	 */
	protected function enableTwoFactor(BackendUser $user, $return)
	{
		// Return if 2FA is enabled already
		if ($user->useTwoFactor)
		{
			return;
		}

		$container = System::getContainer();
		$verifyHelp = $GLOBALS['TL_LANG']['MSC']['twoFactorVerificationHelp'];

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

				throw new RedirectResponseException($return);
			}

			$this->Template->error = true;
			$verifyHelp = $GLOBALS['TL_LANG']['ERR']['invalidTwoFactor'];
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
		$this->Template->verifyHelp = $verifyHelp;
	}

	/**
	 * Disable two-factor authentication
	 *
	 * @param BackendUser $user
	 * @param string      $return
	 */
	protected function disableTwoFactor(BackendUser $user, $return)
	{
		// Return if 2FA is disabled already
		if (!$user->useTwoFactor)
		{
			return;
		}

		$user->secret = null;
		$user->useTwoFactor = '';
		$user->backupCodes = null;
		$user->save();

		throw new RedirectResponseException($return);
	}

	/**
	 * Generate backup codes for two-factor authentication
	 *
	 * @param BackendUser $user
	 */
	private function generateBackupCodes(BackendUser $user): void
	{
		$container = System::getContainer();

		/** @var BackupCodeManager $backupCodeManager */
		$backupCodeManager = $container->get('contao.security.two_factor.backup_code_manager');
		$backupCodeManager->generateBackupCodes($user);
	}
}
