<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Backend;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\Environment;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/login", name="contao_backend_login")
 *
 * @internal
 */
class BackendLoginController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $this->initializeContaoFramework();

        $uriSigner = $this->get('uri_signer');

        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            if ($request->query->has('redirect')) {
                // We cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
                if ($uriSigner->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().(null !== ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : ''))) {
                    return new RedirectResponse($request->query->get('redirect'));
                }
            }

            return new RedirectResponse($this->generateUrl('contao_backend'));
        }

        // Ported from Backend::__construct()
        Controller::setStaticUrls();

        $exception = $this->get('security.authentication_utils')->getLastAuthenticationError();

        $messages = $this->get('contao.framework')->createInstance(Message::class);

        if ($exception instanceof LockedException) {
            $messages->addError(sprintf($this->trans('ERR.accountLocked'), $exception->getLockedMinutes()));
        } elseif ($exception instanceof InvalidTwoFactorCodeException) {
            $messages->addError($this->trans('ERR.invalidTwoFactor'));
        } elseif ($exception instanceof AuthenticationException) {
            $messages->addError($this->trans('ERR.invalidLogin'));
        }

        $targetPath = $this->generateUrl('contao_backend', [], Router::ABSOLUTE_URL);

        if ($request->query->has('redirect')) {
            // We cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
            if ($uriSigner->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().(null !== ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : ''))) {
                $targetPath = $request->query->get('redirect');
            }
        }

        $template = new BackendTemplate('be_login');
        $template->headline = $this->trans('MSC.loginBT');

        $token = $this->get('security.token_storage')->getToken();

        if ($token instanceof TwoFactorToken) {
            // Dispatch 2FA form event to prepare 2FA providers
            $event = new TwoFactorAuthenticationEvent($request, $token);
            $this->get('event_dispatcher')->dispatch($event, TwoFactorAuthenticationEvents::FORM);

            $template = new BackendTemplate('be_login_two_factor');
            $template->headline = $this->trans('MSC.twoFactorAuthentication');
            $template->authCode = $this->trans('MSC.twoFactorVerification');
            $template->cancel = $this->trans('MSC.cancelBT');
        }

        $template->theme = Backend::getTheme();
        $template->messages = $messages->generate();
        $template->base = Environment::get('base');
        $template->language = $GLOBALS['TL_LANGUAGE'];
        $template->languages = System::getLanguages(true); // backwards compatibility
        $template->host = Backend::getDecodedHostname();
        $template->charset = Config::get('characterSet');
        $template->userLanguage = $GLOBALS['TL_LANG']['tl_user']['language'][0];
        $template->curLanguage = Input::post('language') ?: str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
        $template->curUsername = Input::post('username') ?: '';
        $template->loginButton = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['continue']);
        $template->username = $this->trans('tl_user.username.0', [], 'contao_tl_user');
        $template->password = $this->trans('MSC.password.0');
        $template->feLink = $this->trans('MSC.feLink');
        $template->default = $this->trans('MSC.default');
        $template->jsDisabled = $this->trans('MSC.jsDisabled');
        $template->targetPath = StringUtil::specialchars(base64_encode($targetPath));

        return $template->getResponse();
    }

    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['uri_signer'] = UriSigner::class;
        $services['translator'] = TranslatorInterface::class;
        $services['security.authentication_utils'] = AuthenticationUtils::class;

        return $services;
    }

    private function trans(string $key, array $attributes = [], string $domain = 'contao_default')
    {
        return $this->get('translator')->trans($key, $attributes, $domain);
    }
}
