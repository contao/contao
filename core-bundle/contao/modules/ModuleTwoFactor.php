<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Entity\WebauthnCredential;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Repository\WebauthnCredentialRepository;
use ParagonIE\ConstantTime\Base32;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Back end module "two factor".
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
		$security = $container->get('security.helper');

		if (!$security->isGranted('IS_AUTHENTICATED_FULLY'))
		{
			throw new AccessDeniedException('User is not fully authenticated');
		}

		$user = BackendUser::getInstance();

		// Inform the user if 2FA is enforced
		if (!$user->useTwoFactor && !Input::get('act') && $container->getParameter('contao.security.two_factor.enforce_backend'))
		{
			Message::addInfo($GLOBALS['TL_LANG']['MSC']['twoFactorEnforced']);
		}

		$request = $container->get('request_stack')->getCurrentRequest();
		$ref = $request->attributes->get('_contao_referer_id');
		$return = $container->get('router')->generate('contao_backend', array('do'=>'security', 'ref'=>$ref));

		/** @var UriSigner $uriSigner */
		$uriSigner = $container->get('uri_signer');
		$passkeyReturn = $uriSigner->sign($container->get('router')->generate('contao_backend', array('do'=>'security', 'ref'=>$ref, 'edit_new_passkey'=>1), UrlGeneratorInterface::ABSOLUTE_URL));

		$this->Template->href = $this->getReferer(true);
		$this->Template->ref = $ref;
		$this->Template->messages = Message::generateUnwrapped();
		$this->Template->backupCodes = json_decode((string) $user->backupCodes, true) ?? array();

		if (Input::get('act') == 'enable')
		{
			$this->enableTwoFactor($user, $return);
		}

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_disable')
		{
			$this->disableTwoFactor($user, $return);
		}

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_generate_backup_codes')
		{
			$this->Template->showBackupCodes = true;
			$this->Template->backupCodes = System::getContainer()->get('contao.security.two_factor.backup_code_manager')->generateBackupCodes($user);
		}

		if (Input::post('FORM_SUBMIT') == 'tl_two_factor_clear_trusted_devices')
		{
			$container->get('contao.security.two_factor.trusted_device_manager')->clearTrustedDevices($user);
		}

		/** @var WebauthnCredentialRepository $credentialRepo */
		$credentialRepo = $container->get('contao.repository.webauthn_credential');

		if (Input::post('FORM_SUBMIT') === 'tl_passkeys_credentials_actions')
		{
			if ($deleteCredentialId = Input::post('delete_passkey'))
			{
				if ($credential = $credentialRepo->findOneById($deleteCredentialId))
				{
					$this->checkCredentialAccess($user, $credential);

					$credentialRepo->remove($credential);
				}
			}
			elseif ($editCredentialId = Input::post('edit_passkey'))
			{
				if ($credential = $credentialRepo->findOneById($editCredentialId))
				{
					$this->checkCredentialAccess($user, $credential);

					$this->redirect($this->addToUrl('edit_passkey=' . $editCredentialId));
				}
			}

			$this->redirect($this->addToUrl('', true, array('edit_passkey', 'edit_new_passkey')));
		}
		elseif (Input::post('FORM_SUBMIT') === 'tl_passkeys_credentials_edit')
		{
			if ($saveCredentialId = Input::post('credential_id'))
			{
				if ($credential = $credentialRepo->findOneById($saveCredentialId))
				{
					$this->checkCredentialAccess($user, $credential);

					$credential->name = Input::post('passkey_name') ?? '';
					$credentialRepo->saveCredentialSource($credential);
				}
			}

			$this->redirect($this->addToUrl('', true, array('edit_passkey', 'edit_new_passkey')));
		}

		$this->Template->isEnabled = $user->useTwoFactor;
		$this->Template->trustedDevices = $container->get('contao.security.two_factor.trusted_device_manager')->getTrustedDevices($user);
		$this->Template->webauthnCreationSuccessRedirectUri = $passkeyReturn;
		$this->Template->credentials = $credentialRepo->getAllForUser($user);
		$this->Template->editPassKeyId = (string) Input::get('edit_passkey');

		if (Input::get('edit_new_passkey') && $uriSigner->checkRequest($request))
		{
			$lastCredential = $credentialRepo->getLastForUser($user);

			if ($lastCredential instanceof WebauthnCredential)
			{
				$this->Template->editPassKeyId = $lastCredential->getId();
			}
		}
	}

	/**
	 * Enable two-factor authentication
	 *
	 * @param BackendUser $user
	 * @param string      $return
	 */
	protected function enableTwoFactor(BackendUser $user, $return)
	{
		$container = System::getContainer();
		$authenticator = $container->get('contao.security.two_factor.authenticator');
		$verifyHelp = $GLOBALS['TL_LANG']['MSC']['twoFactorVerificationHelp'];

		// Validate the verification code
		if (Input::post('FORM_SUBMIT') == 'tl_two_factor')
		{
			if ($authenticator->validateCode($user, Input::post('verify')))
			{
				// Enable 2FA
				$user->useTwoFactor = true;
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
		$user->useTwoFactor = false;
		$user->backupCodes = null;
		$user->save();

		// Clear all trusted devices
		System::getContainer()->get('contao.security.two_factor.trusted_device_manager')->clearTrustedDevices($user);

		throw new RedirectResponseException($return);
	}

	private function checkCredentialAccess(BackendUser $user, WebauthnCredential $credential): void
	{
		if ($credential->userHandle !== $user->getPasskeyUserHandle())
		{
			throw new AccessDeniedHttpException('Cannot access credential ID ' . $credential->getId());
		}
	}
}
