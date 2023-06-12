<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class PreviewAuthenticationListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TokenChecker $tokenChecker,
        private readonly UrlGeneratorInterface $router,
        private readonly UriSigner $uriSigner,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (
            !$request->attributes->get('_preview', false)
            || $this->scopeMatcher->isBackendRequest($request)
            || $this->tokenChecker->canAccessPreview()
        ) {
            return;
        }

        // Ajax requests must not be redirected to the login screen, instead we
        // redirect to the URL without preview script.
        if ($request->isXmlHttpRequest()) {
            $event->setResponse(new RedirectResponse($request->getSchemeAndHttpHost().$request->getBasePath().$request->getPathInfo().(null !== ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : '')));

            return;
        }

        $context = $this->router->getContext();
        $baseUrl = $context->getBaseUrl();

        $context->setBaseUrl('');

        $url = $this->router->generate(
            'contao_backend_login',
            ['redirect' => $request->getUri()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $context->setBaseUrl($baseUrl);

        $event->setResponse(new RedirectResponse($this->uriSigner->sign($url)));
    }
}
