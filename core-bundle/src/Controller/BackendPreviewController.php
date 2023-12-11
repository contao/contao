<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Nyholm\Psr7\Uri;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * This controller handles the back end preview call and redirects to the
 * requested front end page while ensuring that the /preview.php entry point is
 * used. When requested, the front end user gets authenticated.
 */
#[Route('%contao.backend.route_prefix%', defaults: ['_scope' => 'backend', '_allow_preview' => true])]
class BackendPreviewController
{
    public function __construct(
        private readonly string $previewScript,
        private readonly FrontendPreviewAuthenticator $previewAuthenticator,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly Security $security,
        private readonly LoginLinkHandlerInterface $loginLinkHandler,
        private readonly UriSigner $uriSigner,
    ) {
    }

    #[Route('/preview', name: 'contao_backend_preview')]
    public function __invoke(Request $request): Response
    {
        // Skip the redirect if there is no preview script, otherwise we will
        // end up in an endless loop (see #1511)
        if ($this->previewScript && substr($request->getScriptName(), \strlen($request->getBasePath())) !== $this->previewScript) {
            $qs = $request->getQueryString();

            return new RedirectResponse($request->getBasePath().$this->previewScript.$request->getPathInfo().($qs ? '?'.$qs : ''));
        }

        if (!$this->security->isGranted('ROLE_USER')) {
            return new Response('Access denied', Response::HTTP_FORBIDDEN);
        }

        $frontendUser = $request->query->get('user');

        // Switch to a particular member (see contao/core#6546)
        if ($frontendUser && !$this->previewAuthenticator->authenticateFrontendUser($frontendUser, false)) {
            $this->previewAuthenticator->removeFrontendAuthentication();
        }

        $urlConvertEvent = new PreviewUrlConvertEvent($request);

        $this->dispatcher->dispatch($urlConvertEvent, ContaoCoreEvents::PREVIEW_URL_CONVERT);

        if ($response = $urlConvertEvent->getResponse()) {
            return $response;
        }

        if (!$targetUrl = $urlConvertEvent->getUrl()) {
            return new RedirectResponse($request->getBaseUrl().'/');
        }

        $targetUri = new Uri($targetUrl);

        if ($request->getHost() === $targetUri->getHost() || !($user = $this->security->getUser())) {
            return new RedirectResponse($targetUrl);
        }

        $loginLink = $this->loginLinkHandler->createLoginLink($user, Request::create($targetUrl));
        $loginUri = new Uri($loginLink->getUrl());

        parse_str($loginUri->getQuery(), $query);

        $previewUri = (new Uri($request->getUri()))
            ->withScheme($targetUri->getScheme())
            ->withHost($targetUri->getHost())
        ;

        $query['_target_path'] = base64_encode((string) $previewUri);
        $query[TwoFactorAuthenticator::FLAG_2FA_COMPLETE] = (bool) $this->security->getToken()?->hasAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE);

        $loginUri = $loginUri->withQuery(http_build_query($query));
        $targetUrl = $this->uriSigner->sign((string) $loginUri);

        return new RedirectResponse($targetUrl);
    }
}
