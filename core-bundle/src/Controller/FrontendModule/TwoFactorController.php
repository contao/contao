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
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class TwoFactorController extends AbstractFrontendModuleController
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var Authenticator
     */
    protected $authenticator;

    /**
     * @var AuthenticationUtils
     */
    protected $authenticationUtils;

    /** @var PageModel */
    protected $page;

    public function __construct(TranslatorInterface $translator, Router $router, TokenStorage $tokenStorage, Authenticator $authenticator, AuthenticationUtils $authenticationUtils)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->authenticator = $authenticator;
        $this->authenticationUtils = $authenticationUtils;
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        $this->page = $page;
        $this->page->loadDetails();

        return parent::__invoke($request, $model, $section, $classes);
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof FrontendUser) {
            return $template->getResponse();
        }

        $redirectPage = PageModel::findByPk($model->jumpTo);
        $return = $redirectPage instanceof PageModel ? $redirectPage->getAbsoluteUrl() : $this->page->getAbsoluteUrl();

        $template->error = false;
        $template->action = '';
        $template->enforceTwoFactor = $this->page->enforceTwoFactor;
        $template->targetPath = $return;

        // Inform the user if 2FA is enforced
        if ($this->page->enforceTwoFactor) {
            $template->message = $this->translator->trans('MSC.twoFactorEnforced', [], 'contao_default');
        }

        if ((!$user->useTwoFactor && $this->page->enforceTwoFactor) || 'enable' === $request->get('2fa')) {
            $this->enableTwoFactor($template, $request, $user, $return);
        }

        if (!$this->page->enforceTwoFactor && 'tl_two_factor_disable' === $request->request->get('FORM_SUBMIT')) {
            $this->disableTwoFactor($user);
        }

        $template->isEnabled = $user->useTwoFactor;
        $template->href = $this->router->generate('tl_page.'.$this->page->id, ['2fa' => 'enable']);
        $template->twoFactor = $this->translator->trans('MSC.twoFactorAuthentication', [], 'contao_default');
        $template->explain = $this->translator->trans('MSC.twoFactorExplain', [], 'contao_default');
        $template->active = $this->translator->trans('MSC.twoFactorActive', [], 'contao_default');
        $template->enableButton = $this->translator->trans('MSC.enable', [], 'contao_default');
        $template->disableButton = $this->translator->trans('MSC.disable', [], 'contao_default');

        return $template->getResponse();
    }

    private function enableTwoFactor(Template $template, Request $request, FrontendUser $user, $return): void
    {
        // Return if 2FA is enabled already
        if ($user->useTwoFactor) {
            return;
        }

        /** @var AuthenticationException|null $exception */
        $exception = $this->authenticationUtils->getLastAuthenticationError();

        if ($exception instanceof InvalidTwoFactorCodeException) {
            $template->error = true;
            $template->message = $this->translator->trans('ERR.invalidTwoFactor', [], 'contao_default');
        }

        // Validate the verification code
        if ('tl_two_factor' === $request->request->get('FORM_SUBMIT')) {
            if ($this->authenticator->validateCode($user, $request->request->get('verify'))) {
                // Enable 2FA
                $user->useTwoFactor = '1';
                $user->save();

                throw new RedirectResponseException($return);
            }

            $template->error = true;
            $template->message = $GLOBALS['TL_LANG']['ERR']['invalidTwoFactor'];
        }

        // Generate the secret
        if (!$user->secret) {
            $user->secret = random_bytes(128);
            $user->save();
        }

        $template->enable = true;
        $template->secret = Base32::encodeUpperUnpadded($user->secret);
        $template->textCode = $this->translator->trans('MSC.twoFactorTextCode', [], 'contao_default');
        $template->qrCode = base64_encode($this->authenticator->getQrCode($user, $request));
        $template->scan = $this->translator->trans('MSC.twoFactorScan', [], 'contao_default');
        $template->verify = $this->translator->trans('MSC.twoFactorVerification', [], 'contao_default');
        $template->verifyHelp = $this->translator->trans('MSC.twoFactorVerificationHelp', [], 'contao_default');
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
