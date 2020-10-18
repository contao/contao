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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error as TwigError;

/**
 * Injects the back end preview toolbar on any response within the /preview.php
 * entry point.
 *
 * The onKernelResponse method must be connected to the kernel.response event.
 *
 * The toolbar is only injected on well-formed HTML with a proper </body> tag,
 * so is never included in sub-requests or ESI requests.
 */
class PreviewToolbarListener
{
    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var string
     */
    private $previewScript;

    /**
     * @var TwigEnvironment
     */
    private $twig;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(string $previewScript, ScopeMatcher $scopeMatcher, TwigEnvironment $twig, RouterInterface $router)
    {
        $this->previewScript = $previewScript;
        $this->scopeMatcher = $scopeMatcher;
        $this->twig = $twig;
        $this->router = $router;
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        if ('' === $this->previewScript || $request->getScriptName() !== $this->previewScript) {
            return;
        }

        $response = $event->getResponse();

        // Do not capture redirects, errors, or modify XML HTTP Requests
        if ($request->isXmlHttpRequest() || !($response->isSuccessful() || $response->isClientError())) {
            return;
        }

        // Only inject the toolbar into HTML responses
        if (
            'html' !== $request->getRequestFormat()
            || false === strpos((string) $response->headers->get('Content-Type'), 'text/html')
            || false !== stripos((string) $response->headers->get('Content-Disposition'), 'attachment;')
        ) {
            return;
        }

        $this->injectToolbar($response, $request);
    }

    /**
     * @throws TwigError
     */
    private function injectToolbar(Response $response, Request $request): void
    {
        $content = $response->getContent();
        $pos = strripos($content, '</body>');

        if (false === $pos) {
            return;
        }

        $toolbar = $this->twig->render(
            '@ContaoCore/Frontend/preview_toolbar_base_js.html.twig',
            [
                'action' => $this->router->generate('contao_backend_switch'),
                'request' => $request,
                'preview_script' => $this->previewScript,
            ]
        );

        $response->setContent(substr($content, 0, $pos)."\n".$toolbar."\n".substr($content, $pos));
    }
}
