<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Entity\TrustedDevice;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Repository\TrustedDeviceRepository;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Security\TwoFactor\BackupCodeManager;
use Contao\CoreBundle\Security\TwoFactor\TrustedDeviceManager;
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

		/** @var Request $request */
		$request = $container->get('request_stack')->getMasterRequest();

		/** @var TrustedDeviceRepository $trustedDeviceRepository */
		$trustedDeviceRepository = $container->get('doctrine.orm.entity_manager')->getRepository(TrustedDevice::class);
		$ref = $container->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id');
		$return = $container->get('router')->generate('contao_backend', array('do'=>'security', 'ref'=>$ref));
		$user = BackendUser::getInstance();

		// Inform the user if 2FA is enforced
		if (!$user->useTwoFactor && empty($_GET['act']) && $container->getParameter('contao.security.two_factor.enforce_backend'))
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['twoFactorEnforced']);
		}

		$ref = $container->get('request_stack')->getCurrentRequest()->attributes->get('_contao_referer_id');
		$return = $container->get('router')->generate('contao_backend', array('do'=>'security', 'ref'=>$ref));

		$this->Template->href = $this->getReferer(true);
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

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_show_backup_codes')
		{
			if (!$user->backupCodes || !\count(json_decode($user->backupCodes, true)))
			{
				$this->generateBackupCodes($user);
			}

			$this->Template->showBackupCodes = true;
		}

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_generate_backup_codes')
		{
			$this->generateBackupCodes($user);

			$this->Template->showBackupCodes = true;
		}

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_clear_trusted_devices')
		{
			$this->clearTrustedDevices($user, $return);
		}

		$this->Template->isEnabled = (bool) $user->useTwoFactor;
		$this->Template->backupCodes = json_decode((string) $user->backupCodes, true) ?? array();

		$this->Template->isEnabled = (bool) $this->User->useTwoFactor;
		$this->Template->twoFactor = $GLOBALS['TL_LANG']['MSC']['twoFactorAuthentication'];
		$this->Template->explain = $GLOBALS['TL_LANG']['MSC']['twoFactorExplain'];
		$this->Template->active = $GLOBALS['TL_LANG']['MSC']['twoFactorActive'];
		$this->Template->enableButton = $GLOBALS['TL_LANG']['MSC']['enable'];
		$this->Template->disableButton = $GLOBALS['TL_LANG']['MSC']['disable'];
		$this->Template->trustedDevicesLabel = $GLOBALS['TL_LANG']['MSC']['trustedDevices'];
		$this->Template->deviceLabel = $GLOBALS['TL_LANG']['MSC']['device'];
		$this->Template->browserLabel = $GLOBALS['TL_LANG']['MSC']['browser'];
		$this->Template->operatingSystemLabel = $GLOBALS['TL_LANG']['MSC']['operatingSystem'];
		$this->Template->clearTrustedDevicesButton = $GLOBALS['TL_LANG']['MSC']['clearTrustedDevices'];
		$this->Template->trustedDevices = $trustedDeviceRepository->findForUser($user);
		$this->Template->currentDevice = $request->cookies->get($container->getParameter('scheb_two_factor.trusted_device.cookie_name'));
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
		$this->Template->qrCode = base64_encode($authenticator->getQrCode($user, $request));
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

		// clear all trusted devices
		$this->clearTrustedDevices($user, $return);
	}

	/**
	 * Generate backup codes for two-factor authentication
	 *
	 * @param BackendUser $user
	 */
	private function generateBackupCodes(BackendUser $user)
	{
		/** @var BackupCodeManager $backupCodeManager */
		$backupCodeManager = System::getContainer()->get(BackupCodeManager::class);
		$backupCodeManager->generateBackupCodes($user);
	}

	/**
	 * Clears trusted devices with incrementing the trustedVersion number
	 *
	 * @param BackendUser $user
	 * @param $return
	 */
	protected function clearTrustedDevices(BackendUser $user, $return)
	{
		$container = System::getContainer();

		/** @var TrustedDeviceManager $trustedDeviceManager */
		$trustedDeviceManager = $container->get(TrustedDeviceManager::class);
		$trustedDeviceManager->clearTrustedDevices($user);

		throw new RedirectResponseException($return);
	}
}
