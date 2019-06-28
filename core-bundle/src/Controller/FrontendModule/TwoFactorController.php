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

use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Translation\Translator;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class TwoFactorController extends AbstractFrontendModuleController
{
    /** @var PageModel */
    protected $page;

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        $this->page = $page;

        if ($this->page instanceof PageModel && $this->get('contao.routing.scope_matcher')->isFrontendRequest($request)) {
            $this->page->loadDetails();
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        /** @var Translator $translator */
        $translator = $this->get('contao.translation.translator');
        $token = $this->get('security.token_storage')->getToken();

        if (!$token instanceof TokenInterface) {
            return $template->getResponse();
        }

        $user = $token->getUser();

        if (!$user instanceof FrontendUser) {
            return $template->getResponse();
        }

        /** @var PageModel $adapter */
        $adapter = $this->get('contao.framework')->getAdapter(PageModel::class);

        $redirectPage = $model->jumpTo > 0 ? $adapter->findByPk($model->jumpTo) : null;
        $return = $redirectPage instanceof PageModel ? $redirectPage->getAbsoluteUrl() : $this->page->getAbsoluteUrl();

        $template->error = false;
        $template->action = '';
        $template->enforceTwoFactor = $this->page->enforceTwoFactor;
        $template->targetPath = $return;

        // Inform the user if 2FA is enforced
        if ($this->page->enforceTwoFactor) {
            $template->message = $translator->trans('MSC.twoFactorEnforced', [], 'contao_default');
        }

        if ((!$user->useTwoFactor && $this->page->enforceTwoFactor) || 'enable' === $request->get('2fa')) {
            $this->enableTwoFactor($template, $request, $user, $return);
        }

        if (!$this->page->enforceTwoFactor && 'tl_two_factor_disable' === $request->request->get('FORM_SUBMIT')) {
            $this->disableTwoFactor($user);
        }

        $template->isEnabled = $user->useTwoFactor;
        $template->href = $this->page->getAbsoluteUrl().'?2fa=enable';
        $template->twoFactor = $translator->trans('MSC.twoFactorAuthentication', [], 'contao_default');
        $template->explain = $translator->trans('MSC.twoFactorExplain', [], 'contao_default');
        $template->active = $translator->trans('MSC.twoFactorActive', [], 'contao_default');
        $template->enableButton = $translator->trans('MSC.enable', [], 'contao_default');
        $template->disableButton = $translator->trans('MSC.disable', [], 'contao_default');

        return $template->getResponse();
    }

    private function enableTwoFactor(Template $template, Request $request, FrontendUser $user, $return): void
    {
        // Return if 2FA is enabled already
        if ($user->useTwoFactor) {
            return;
        }

        /** @var Translator $translator */
        $translator = $this->get('contao.translation.translator');

        /** @var Authenticator $authenticator */
        $authenticator = $this->get('contao.security.two_factor.authenticator');

        /** @var AuthenticationException|null $exception */
        $exception = $this->get('security.authentication_utils')->getLastAuthenticationError();

        if ($exception instanceof InvalidTwoFactorCodeException) {
            $template->error = true;
            $template->message = $translator->trans('ERR.invalidTwoFactor', [], 'contao_default');
        }

        // Validate the verification code
        if ('tl_two_factor' === $request->request->get('FORM_SUBMIT')) {
            if ($authenticator->validateCode($user, $request->request->get('verify'))) {
                // Enable 2FA
                $user->useTwoFactor = '1';
                $user->save();

                throw new RedirectResponseException($return);
            }

            $template->error = true;
            $template->message = $translator->trans('ERR.invalidTwoFactor', [], 'contao_default');
        }

        // Generate the secret
        if (!$user->secret) {
            $user->secret = random_bytes(128);
            $user->save();
        }

        $template->enable = true;
        $template->secret = Base32::encodeUpperUnpadded($user->secret);
        $template->textCode = $translator->trans('MSC.twoFactorTextCode', [], 'contao_default');
        $template->qrCode = base64_encode($authenticator->getQrCode($user, $request));
        $template->scan = $translator->trans('MSC.twoFactorScan', [], 'contao_default');
        $template->verify = $translator->trans('MSC.twoFactorVerification', [], 'contao_default');
        $template->verifyHelp = $translator->trans('MSC.twoFactorVerificationHelp', [], 'contao_default');
    }

    private function disableTwoFactor(FrontendUser $user): void
    {
        // Return if 2FA is disabled already
        if (!$user->useTwoFactor) {
            return;
        }

        $user->secret = null;
        $user->useTwoFactor = '';
        $user->save();

        throw new RedirectResponseException($this->page->getAbsoluteUrl());
    }
}
