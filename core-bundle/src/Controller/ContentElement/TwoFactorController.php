<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Security\TwoFactor\BackupCodeManager;
use Contao\CoreBundle\Security\TwoFactor\TrustedDeviceManager;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\PageModel;
use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

// todo: should this be in a new 'user' or 'security' category?
#[AsContentElement(category: 'miscellaneous')]
class TwoFactorController extends AbstractContentElementController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly BackupCodeManager $backupCodeManager,
        private readonly TrustedDeviceManager $trustedDeviceManager,
        private readonly Authenticator $authenticator,
        private readonly AuthenticationUtils $authenticationUtils,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $user = $this->getUser();
        $pageModel = $this->getPageModel();

        if (!$user instanceof FrontendUser || !$pageModel instanceof PageModel) {
            $template->set('can_use_2fa', false);

            return $template->getResponse();
        }
        $template->set('can_use_2fa', true);

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY', null, 'Full authentication is required to configure the two-factor authentication.');

        $targetPage = $this->getTargetPage($model, $pageModel);

        // Enable 2FA if it is forced in the page settings or was requested by a user
        if ((!$user->useTwoFactor && $pageModel->enforceTwoFactor) || 'enable' === $request->get('2fa')) {
            $exception = $this->authenticationUtils->getLastAuthenticationError();

            // Validate the verification code
            $invalidCode = false;

            if ('tl_two_factor' === $request->request->get('FORM_SUBMIT')) {
                // Enable 2FA
                if ($this->authenticator->validateCode($user, $request->request->get('verify'))) {
                    $this->enable2FA($user);

                    return new RedirectResponse($this->generateContentUrl($targetPage, [], UrlGeneratorInterface::ABSOLUTE_URL));
                }

                $invalidCode = true;
            } elseif ($exception instanceof InvalidTwoFactorCodeException) {
                $invalidCode = true;
            }

            // Generate the secret
            $this->ensureHasSecret($user);

            $template->set('enable', true);
            $template->set('invalid_verification_code', $invalidCode);
            $template->set('code', $this->generateCodeData($user, $request));
        }

        $formId = $request->request->get('FORM_SUBMIT');

        // Disable 2FA if it was requested by a user
        if ('tl_two_factor_disable' === $formId && $user->useTwoFactor) {
            // todo: require !$pageModel->enforceTwoFactor?
            $this->disable2FA($user);

            return new RedirectResponse($this->generateContentUrl($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        $showBackupCodes = false;

        if ('tl_two_factor_clear_trusted_devices' === $formId) {
            $this->trustedDeviceManager->clearTrustedDevices($user);
        } elseif ('tl_two_factor_generate_backup_codes' === $formId) {
            $template->set('backup_codes', $this->backupCodeManager->generateBackupCodes($user));
            $showBackupCodes = true;
        } else {
            try {
                $template->set('backup_codes', json_decode((string) $user->backupCodes, true, 512, JSON_THROW_ON_ERROR));
            } catch (\JsonException) {
                $template->set('backup_codes', []);
            }
        }

        $template->set('is_enabled', $user->useTwoFactor);
        $template->set('enforce_two_factor', $pageModel->enforceTwoFactor);

        $template->set('show_backup_codes', $showBackupCodes);
        $template->set('trusted_devices', $this->trustedDeviceManager->getTrustedDevices($user));
        $template->set('enable_url', $this->generateContentUrl($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL).'?2fa=enable');
        $template->set('target_page', $targetPage);

        return $template->getResponse();
    }

    private function getTargetPage(ContentModel $model, PageModel $pageModel): PageModel
    {
        $adapter = $this->framework->getAdapter(PageModel::class);
        // todo: there is no jumpTo on tl_content, yet
        $redirectPage = $model->jumpTo > 0 ? $adapter->findById($model->jumpTo) : null;

        return $redirectPage instanceof PageModel ? $redirectPage : $pageModel;
    }

    private function enable2FA(FrontendUser $user): void
    {
        $user->useTwoFactor = true;
        $user->save();
    }

    private function disable2FA(FrontendUser $user): void
    {
        $user->secret = null;
        $user->useTwoFactor = false;
        $user->backupCodes = null;
        $user->save();

        // Clear all trusted devices
        $this->trustedDeviceManager->clearTrustedDevices($user);
    }

    private function ensureHasSecret(FrontendUser $user): void
    {
        if (!$user->secret) {
            $user->secret = random_bytes(128);
            $user->save();
        }
    }

    /**
     * @return array<string, string>
     */
    private function generateCodeData(FrontendUser $user, Request $request): array
    {
        return [
            'secret' => Base32::encodeUpperUnpadded($user->secret),
            'qr_image' => base64_encode($this->authenticator->getQrCode($user, $request)),
        ];
    }
}
