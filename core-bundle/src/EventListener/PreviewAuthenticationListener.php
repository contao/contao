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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class PreviewAuthenticationListener
{
    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var TokenChecker
     */
    private $tokenChecker;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var UriSigner
     */
    private $uriSigner;

    /**
     * @var string
     */
    private $previewScript;

    public function __construct(ScopeMatcher $scopeMatcher, TokenChecker $tokenChecker, UrlGeneratorInterface $router, UriSigner $uriSigner, string $previewScript)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenChecker = $tokenChecker;
        $this->router = $router;
        $this->uriSigner = $uriSigner;
        $this->previewScript = $previewScript;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (
            '' === $this->previewScript
            || $request->getScriptName() !== $this->previewScript
            || !$this->scopeMatcher->isFrontendRequest($request)
            || $this->tokenChecker->hasBackendUser()
        ) {
            return;
        }

        $url = $this->router->generate(
            'contao_backend_login',
            ['redirect' => $request->getUri()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $event->setResponse(new RedirectResponse($this->uriSigner->sign($url), Response::HTTP_TEMPORARY_REDIRECT));
    }
}
