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
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class TwoFactorController extends AbstractFrontendModuleController
{
    /**
     * @var PageModel
     */
    protected $pageModel;

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $pageModel = null): Response
    {
        if (!$this->get('security.helper')->isGranted('IS_AUTHENTICATED_FULLY')) {
            // TODO: front end users should be able to re-authenticate after REMEMBERME
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $this->pageModel = $pageModel;

        if (
            $this->pageModel instanceof PageModel
            && $this->get('contao.routing.scope_matcher')->isFrontendRequest($request)
        ) {
            $this->pageModel->loadDetails();
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['contao.routing.scope_matcher'] = ScopeMatcher::class;
        $services['contao.security.two_factor.authenticator'] = Authenticator::class;
        $services['security.authentication_utils'] = AuthenticationUtils::class;
        $services['security.helper'] = Security::class;
        $services['translator'] = TranslatorInterface::class;
        $services['contao.security.two_factor.trusted_device_manager'] = TrustedDeviceManager::class;
        $services[BackupCodeManager::class] = BackupCodeManager::class;

        return $services;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $user = $this->get('security.helper')->getUser();

        if (!$user instanceof FrontendUser) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        /** @var PageModel $adapter */
        $adapter = $this->get('contao.framework')->getAdapter(PageModel::class);

        $redirectPage = $model->jumpTo > 0 ? $adapter->findByPk($model->jumpTo) : null;
        $return = $redirectPage instanceof PageModel ? $redirectPage->getAbsoluteUrl() : $this->pageModel->getAbsoluteUrl();

        $template->enforceTwoFactor = $this->pageModel->enforceTwoFactor;
        $template->targetPath = $return;

        $translator = $this->get('translator');

        // Inform the user if 2FA is enforced
        if ($this->pageModel->enforceTwoFactor) {
            $template->message = $translator->trans('MSC.twoFactorEnforced', [], 'contao_default');
        }

        if ((!$user->useTwoFactor && $this->pageModel->enforceTwoFactor) || 'enable' === $request->get('2fa')) {
            $response = $this->enableTwoFactor($template, $request, $user, $return);

            if (null !== $response) {
                return $response;
            }
        }

        if ('tl_two_factor_disable' === $request->request->get('FORM_SUBMIT')) {
            $response = $this->disableTwoFactor($user);

            if (null !== $response) {
                return $response;
            }
        }

        if ('tl_two_factor_show_backup_codes' === $request->request->get('FORM_SUBMIT')) {
            if (!$user->backupCodes || !\count(json_decode($user->backupCodes, true))) {
                $this->generateBackupCodes($user);
            }

            $template->showBackupCodes = true;
        }

        if ('tl_two_factor_generate_backup_codes' === $request->request->get('FORM_SUBMIT')) {
            $this->generateBackupCodes($user);

            $template->showBackupCodes = true;
        }

        if ('tl_two_factor_clear_trusted_devices' === $request->request->get('FORM_SUBMIT')) {
            $this->get('contao.security.two_factor.trusted_device_manager')->clearTrustedDevices($user);
        }

        $template->isEnabled = (bool) $user->useTwoFactor;
        $template->href = $this->pageModel->getAbsoluteUrl().'?2fa=enable';
        $template->backupCodes = json_decode((string) $user->backupCodes, true) ?? [];
        $template->trustedDevices = $this->get('contao.security.two_factor.trusted_device_manager')->getTrustedDevices($user);

        return new Response($template->parse());
    }

    private function enableTwoFactor(Template $template, Request $request, FrontendUser $user, string $return): ?Response
    {
        // Return if 2FA is enabled already
        if ($user->useTwoFactor) {
            return null;
        }

        $translator = $this->get('translator');
        $authenticator = $this->get('contao.security.two_factor.authenticator');
        $exception = $this->get('security.authentication_utils')->getLastAuthenticationError();

        if ($exception instanceof InvalidTwoFactorCodeException) {
            $template->message = $translator->trans('ERR.invalidTwoFactor', [], 'contao_default');
        }

        // Validate the verification code
        if ('tl_two_factor' === $request->request->get('FORM_SUBMIT')) {
            if ($authenticator->validateCode($user, $request->request->get('verify'))) {
                // Enable 2FA
                $user->useTwoFactor = '1';
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

    private function disableTwoFactor(FrontendUser $user): ?Response
    {
        // Return if 2FA is disabled already
        if (!$user->useTwoFactor) {
            return null;
        }

        $user->secret = null;
        $user->useTwoFactor = '';
        $user->backupCodes = null;
        $user->save();

        // Clear all trusted devices
        $this->get('contao.security.two_factor.trusted_device_manager')->clearTrustedDevices($user);

        return new RedirectResponse($this->pageModel->getAbsoluteUrl());
    }

    private function generateBackupCodes(FrontendUser $user): void
    {
        /** @var BackupCodeManager $backupCodeManager */
        $backupCodeManager = $this->get(BackupCodeManager::class);
        $backupCodeManager->generateBackupCodes($user);
    }
}
