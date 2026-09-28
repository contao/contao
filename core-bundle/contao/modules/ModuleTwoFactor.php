<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Entity\PersonalAccessToken;
use Contao\CoreBundle\Entity\WebauthnCredential;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Repository\PersonalAccessTokenRepository;
use Contao\CoreBundle\Repository\WebauthnCredentialRepository;
use Contao\CoreBundle\Security\Authentication\AccessTokenManager;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use ParagonIE\ConstantTime\Base32;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

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

		/** @var Request $request */
		$request = $container->get('request_stack')->getCurrentRequest();
		$return = $container->get('router')->generate('contao_backend', array('do'=>'security'));

		/** @var UriSigner $uriSigner */
		$uriSigner = $container->get('uri_signer');
		$passkeyReturn = $uriSigner->sign($container->get('router')->generate('contao_backend', array('do'=>'security', 'edit_new_passkey'=>1), UrlGeneratorInterface::ABSOLUTE_URL));

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

		// Passkeys
		/** @var WebauthnCredentialRepository $credentialRepo */
		$credentialRepo = $container->get('contao.repository.webauthn_credential');

		if (Input::post('FORM_SUBMIT') === 'tl_passkeys_credentials_actions')
		{
			if ($deleteCredentialId = Input::post('delete_passkey'))
			{
				if ($credential = $credentialRepo->findOneById($deleteCredentialId))
				{
					$this->checkWebauthnCredentialAccess($credential);

					$credentialRepo->remove($credential);
				}
			}
			elseif ($editCredentialId = Input::post('edit_passkey'))
			{
				if ($credential = $credentialRepo->findOneById($editCredentialId))
				{
					$this->checkWebauthnCredentialAccess($credential);

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
					$this->checkWebauthnCredentialAccess($credential);

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

		// Personal access tokens
		/** @var PersonalAccessTokenRepository $patRepo */
		$patRepo = $container->get(PersonalAccessTokenRepository::class);
		/** @var AccessTokenManager $accessTokenManager */
		$accessTokenManager = $container->get('contao.security.access_token_manager');
		$session = $request->getSession();

		if (Input::post('FORM_SUBMIT') === 'tl_pat_actions')
		{
			if ($deletePatId = Input::post('delete_pat'))
			{
				if ($accessToken = $patRepo->findOneById($deletePatId))
				{
					$this->checkPersonalAccessTokenAccess($accessToken);

					$patRepo->remove($accessToken);
				}
			}

			$this->redirect($container->get('router')->generate('contao_backend', array('do'=>'security')));
		}

		if (Input::get('act') === 'create_pat')
		{
			$translator = $container->get('translator');

			$nameField = array(
				'label' => $translator->trans('MSC.personalAccessTokenName', array(), 'contao_default'),
				'eval' => array(
					'mandatory' => true,
					'maxlength' => 255
				),
			);

			$expiresField = array(
				'label' => $translator->trans('MSC.personalAccessTokenExpiresIn', array(), 'contao_default'),
				'eval' => array(
					'includeBlankOption' => true,
					'mandatory' => true,
				),
				'options' => array('7', '30', '60', '90', '365', 'unlimited'),
				'reference' => &$GLOBALS['TL_LANG']['MSC']['personalAccessTokenExpiresAtOptions'],
			);

			$nameWidget = new TextField(TextField::getAttributesFromDca($nameField, 'pat_name'));
			$expiresWidget = new SelectMenu(SelectMenu::getAttributesFromDca($expiresField, 'pat_expires'));

			if (Input::post('FORM_SUBMIT') === 'tl_pat_create')
			{
				$nameWidget->validate();
				$expiresWidget->validate();

				if (!$nameWidget->hasErrors() && !$expiresWidget->hasErrors())
				{
					$expiresAt = null;

					if ($expiresWidget->value && 'unlimited' !== $expiresWidget->value)
					{
						$expiresAt = (new \DateTimeImmutable())->modify(\sprintf('+%d days', $expiresWidget->value));
					}

					$personalAccessToken = $accessTokenManager->createToken($user, $nameWidget->value, $expiresAt);

					$session->set('_created_pat_token', $personalAccessToken->getPlainToken());

					$this->redirect($container->get('router')->generate('contao_backend', array('do'=>'security')));
				}

				$container->get('request_stack')->getMainRequest()->attributes->set('_contao_widget_error', true);
			}

			$this->Template->create_pat = true;
			$this->Template->pat_name_widget = $nameWidget;
			$this->Template->pat_expires_widget = $expiresWidget;
		}

		if ($token = $session->get('_created_pat_token'))
		{
			$parsedToken = $accessTokenManager->parseToken($token);

			$this->Template->created_pat_id = Uuid::fromString($parsedToken['id']);
			$this->Template->created_pat_token = $token;
		}

		$this->Template->personal_access_tokens = $patRepo->getAllForUser((int) $user->id);

		$session->remove('_created_pat_token');
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
		// Return if 2FA is already disabled
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

	private function checkWebauthnCredentialAccess(WebauthnCredential $credential): void
	{
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP, $credential))
		{
			throw new AccessDeniedHttpException('Cannot access credential ID ' . $credential->getId());
		}
	}

	private function checkPersonalAccessTokenAccess(PersonalAccessToken $accessToken): void
	{
		if (!System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::PERSONAL_ACCESS_TOKEN_OWNERSHIP, $accessToken))
		{
			throw new AccessDeniedHttpException('Cannot access personal access token ID ' . $accessToken->getId());
		}
	}
}
