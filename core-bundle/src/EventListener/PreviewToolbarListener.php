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
 * PreviewToolbarListener injects the back end preview toolbar on any response within the /preview.php entry point.
 *
 * The onKernelResponse method must be connected to the kernel.response event.
 *
 * The toolbar is only injected on well-formed HTML (with a proper <body> tag).
 * This means that the toolbar is never included in sub-requests or ESI requests.
 */
class PreviewToolbarListener
{
    private $scopeMatcher;

    private $previewScript;

    private $twig;

    private $router;

    public function __construct(
        string $previewScript,
        ScopeMatcher $scopeMatcher,
        TwigEnvironment $twig,
        RouterInterface $router
    ) {
        $this->previewScript = $previewScript;
        $this->scopeMatcher = $scopeMatcher;
        $this->twig = $twig;
        $this->router = $router;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isContaoMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getScriptName() !== $this->previewScript) {
            return;
        }

        // Do not capture redirects, errors, or modify XML HTTP Requests
        if (!$response->isOk() || $request->isXmlHttpRequest()) {
            return;
        }

        // Only inject toolbar on html responses
        if ('html' !== $request->getRequestFormat()
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

        if (false === strpos($content, '<body')) {
            return;
        }

        $toolbar = $this->twig->render(
            '@ContaoCore/Frontend/preview_toolbar_base_js.html.twig',
            [
                'action' => $this->router->generate('contao_backend_preview_switch'),
                'request' => $request,
            ]
        );

        $content = preg_replace(
            '/<body[\\s\\S]*?>/',
            "\$0\n".$toolbar."\n",
            $content
        );

        $response->setContent($content);
    }
}
