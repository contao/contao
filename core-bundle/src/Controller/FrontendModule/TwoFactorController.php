<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\FrontendModule;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Security\TwoFactor\BackupCodeManager;
use Contao\CoreBundle\Security\TwoFactor\TrustedDeviceManager;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsFrontendModule(category: 'user', template: 'mod_two_factor')]
class TwoFactorController extends AbstractFrontendModuleController
{
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['contao.security.two_factor.authenticator'] = Authenticator::class;
        $services['security.authentication_utils'] = AuthenticationUtils::class;
        $services['translator'] = TranslatorInterface::class;
        $services['contao.security.two_factor.trusted_device_manager'] = TrustedDeviceManager::class;
        $services['contao.security.two_factor.backup_code_manager'] = BackupCodeManager::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $user = $this->getUser();
        $pageModel = $request->attributes->get('pageModel');

        if (!$user instanceof FrontendUser || !$pageModel instanceof PageModel) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY', null, 'Full authentication is required to configure the two-factor authentication.');

        $adapter = $this->getContaoAdapter(PageModel::class);
        $redirectPage = $model->jumpTo > 0 ? $adapter->findById($model->jumpTo) : null;
        $return = $this->generateContentUrl($redirectPage instanceof PageModel ? $redirectPage : $pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL);

        $template->enforceTwoFactor = $pageModel->enforceTwoFactor;
        $template->targetPath = $return;

        $translator = $this->container->get('translator');

        // Inform the user if 2FA is enforced
        if ($pageModel->enforceTwoFactor) {
            $template->message = $translator->trans('MSC.twoFactorEnforced', [], 'contao_default');
        }

        $enable = 'enable' === $request->get('2fa');

        if (!$user->useTwoFactor && $pageModel->enforceTwoFactor) {
            $enable = true;
        }

        if ($enable && ($response = $this->enableTwoFactor($template, $request, $user, $return))) {
            return $response;
        }

        $formId = $request->request->get('FORM_SUBMIT');

        if ('tl_two_factor_disable' === $formId && ($response = $this->disableTwoFactor($user, $pageModel))) {
            return $response;
        }

        try {
            $template->backupCodes = json_decode((string) $user->backupCodes, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $template->backupCodes = [];
        }

        if ('tl_two_factor_generate_backup_codes' === $formId) {
            $template->showBackupCodes = true;
            $template->backupCodes = $this->container->get('contao.security.two_factor.backup_code_manager')->generateBackupCodes($user);
        }

        if ('tl_two_factor_clear_trusted_devices' === $formId) {
            $this->container->get('contao.security.two_factor.trusted_device_manager')->clearTrustedDevices($user);
        }

        $template->isEnabled = (bool) $user->useTwoFactor;
        $template->href = $this->generateContentUrl($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL).'?2fa=enable';
        $template->trustedDevices = $this->container->get('contao.security.two_factor.trusted_device_manager')->getTrustedDevices($user);

        return $template->getResponse();
    }

    private function enableTwoFactor(Template $template, Request $request, FrontendUser $user, string $return): Response|null
    {
        // Return if 2FA is enabled already
        if ($user->useTwoFactor) {
            return null;
        }

        $translator = $this->container->get('translator');
        $authenticator = $this->container->get('contao.security.two_factor.authenticator');
        $exception = $this->container->get('security.authentication_utils')->getLastAuthenticationError();

        if ($exception instanceof InvalidTwoFactorCodeException) {
            $template->message = $translator->trans('ERR.invalidTwoFactor', [], 'contao_default');
        }

        // Validate the verification code
        if ('tl_two_factor' === $request->request->get('FORM_SUBMIT')) {
            if ($authenticator->validateCode($user, $request->request->get('verify'))) {
                // Enable 2FA
                $user->useTwoFactor = true;
                $user->save();

                return new RedirectResponse($return);
            }

            $template->message = $translator->trans('ERR.invalidTwoFactor', [], 'contao_default');
        }

        // Generate the secret
        if (!$user->secret) {
            $user->secret = random_bytes(128);
            $user->save();
        }

        $template->enable = true;
        $template->secret = Base32::encodeUpperUnpadded($user->secret);
        $template->qrCode = base64_encode($authenticator->getQrCode($user, $request));

        return null;
    }

    private function disableTwoFactor(FrontendUser $user, PageModel $pageModel): Response|null
    {
        // Return if 2FA is disabled already
        if (!$user->useTwoFactor) {
            return null;
        }

        $user->secret = null;
        $user->useTwoFactor = false;
        $user->backupCodes = null;
        $user->save();

        // Clear all trusted devices
        $this->container->get('contao.security.two_factor.trusted_device_manager')->clearTrustedDevices($user);

        return new RedirectResponse($this->generateContentUrl($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL));
    }
}
