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
    private ScopeMatcher $scopeMatcher;
    private TokenChecker $tokenChecker;
    private UrlGeneratorInterface $router;
    private UriSigner $uriSigner;

    public function __construct(ScopeMatcher $scopeMatcher, TokenChecker $tokenChecker, UrlGeneratorInterface $router, UriSigner $uriSigner)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenChecker = $tokenChecker;
        $this->router = $router;
        $this->uriSigner = $uriSigner;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (
            !$request->attributes->get('_preview', false)
            || $this->scopeMatcher->isBackendRequest($request)
            || $this->tokenChecker->hasBackendUser()
        ) {
            return;
        }

        $url = $this->router->generate(
            'contao_backend_login',
            ['redirect' => $request->getUri()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $event->setResponse(new RedirectResponse($this->uriSigner->sign($url)));
    }
}
