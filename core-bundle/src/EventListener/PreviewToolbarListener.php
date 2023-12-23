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

use Contao\CoreBundle\Csp\CspParser;
use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;

/**
 * Injects the back end preview toolbar on any response within the /preview.php
 * entry point.
 *
 * The onKernelResponse method must be connected to the "kernel.response" event.
 *
 * The toolbar is only injected on well-formed HTML with a proper </body> tag,
 * so is never included in sub-requests or ESI requests.
 *
 * @internal
 */
class PreviewToolbarListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TokenChecker $tokenChecker,
        private readonly TwigEnvironment $twig,
        private readonly RouterInterface $router,
        private readonly CspParser $cspParser,
        private readonly string $previewScript = '',
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Do not capture redirects, errors, or modify XML HTTP Requests
        if (
            !$request->attributes->get('_preview', false)
            || $request->isXmlHttpRequest()
            || !$response->isSuccessful() && !$response->isClientError()
        ) {
            return;
        }

        // Do not inject the toolbar in the back end
        if ($this->scopeMatcher->isBackendMainRequest($event) || !$this->tokenChecker->hasBackendUser()) {
            return;
        }

        // Only inject the toolbar into HTML responses
        if (
            'html' !== $request->getRequestFormat()
            || !str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            || false !== stripos((string) $response->headers->get('Content-Disposition'), 'attachment;')
        ) {
            return;
        }

        $this->injectToolbar($response, $request);
    }

    private function injectToolbar(Response $response, Request $request): void
    {
        $content = $response->getContent();
        $pos = strripos($content, '</body>');

        if (false === $pos) {
            return;
        }

        $cspHandler = $this->createCspHandler($response);

        $toolbar = $this->twig->render('@ContaoCore/Frontend/preview_toolbar_base_js.html.twig', [
            'action' => $this->router->generate('contao_backend_switch'),
            'request' => $request,
            'preview_script' => $this->previewScript,
            'csp_handler' => $cspHandler,
        ]);

        $response->setContent(substr($content, 0, $pos)."\n".$toolbar."\n".substr($content, $pos));

        $cspHandler->applyHeaders($response, $request);
    }

    private function createCspHandler(Response $response): CspHandler
    {
        if ($cspHeader = $response->headers->get('Content-Security-Policy-Report-Only')) {
            $reportOnly = true;
        } else {
            $cspHeader = $response->headers->get('Content-Security-Policy', '');
        }

        $directives = $this->cspParser->parseHeader($cspHeader);
        $directives->setLevel1Fallback(false);

        return new CspHandler($directives, $reportOnly ?? false);
    }
}
