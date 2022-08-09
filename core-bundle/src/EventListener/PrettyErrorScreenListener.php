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

use Contao\Config;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageError404;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Twig\Error\Error;

/**
 * @internal
 */
class PrettyErrorScreenListener
{
    /**
     * @var bool
     */
    private $prettyErrorScreens;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Security
     */
    private $security;

    public function __construct(bool $prettyErrorScreens, Environment $twig, ContaoFramework $framework, Security $security)
    {
        $this->prettyErrorScreens = $prettyErrorScreens;
        $this->twig = $twig;
        $this->framework = $framework;
        $this->security = $security;
    }

    /**
     * Map an exception to an error screen.
     */
    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ('html' !== $request->getRequestFormat()) {
            return;
        }

        if (!AcceptHeader::fromString($request->headers->get('Accept'))->has('text/html')) {
            return;
        }

        $this->handleException($event);
    }

    private function handleException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        try {
            $isBackendUser = $this->security->isGranted('ROLE_USER');
        } catch (AuthenticationCredentialsNotFoundException $e) {
            $isBackendUser = false;
        }

        switch (true) {
            case $isBackendUser:
                $this->renderBackendException($event);
                break;

            case $exception instanceof UnauthorizedHttpException:
                $this->renderErrorScreenByType(401, $event);
                break;

            case $exception instanceof AccessDeniedHttpException:
                $this->renderErrorScreenByType(403, $event);
                break;

            case $exception instanceof NotFoundHttpException:
                $this->renderErrorScreenByType(404, $event);
                break;

            case $exception instanceof ServiceUnavailableHttpException:
                $this->renderTemplate('service_unavailable', 503, $event);
                break;

            default:
                $this->renderErrorScreenByException($event);
        }
    }

    private function renderBackendException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->renderTemplate('backend', $this->getStatusCodeForException($exception), $event);
    }

    private function renderErrorScreenByType(int $type, ExceptionEvent $event): void
    {
        static $processing;

        if (true === $processing) {
            return;
        }

        $processing = true;

        if (null !== ($response = $this->getResponseFromPageHandler($type))) {
            $event->setResponse($response);
        }

        $processing = false;
    }

    private function getResponseFromPageHandler(int $type): ?Response
    {
        $this->framework->initialize(true);

        $key = 'error_'.$type;

        if (!isset($GLOBALS['TL_PTY'][$key]) || !class_exists($GLOBALS['TL_PTY'][$key])) {
            return null;
        }

        /** @var PageError404 $pageHandler */
        $pageHandler = new $GLOBALS['TL_PTY'][$key]();

        try {
            return $pageHandler->getResponse();
        } catch (ResponseException $e) {
            return $e->getResponse();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Checks the exception chain for a known exception.
     */
    private function renderErrorScreenByException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $statusCode = $this->getStatusCodeForException($exception);
        $template = null;

        // Look for a template
        do {
            if ($exception instanceof InvalidRequestTokenException) {
                $template = 'invalid_request_token';
            }
        } while (null === $template && null !== ($exception = $exception->getPrevious()));

        $this->renderTemplate($template ?: 'error', $statusCode, $event);
    }

    private function renderTemplate(string $template, int $statusCode, ExceptionEvent $event): void
    {
        if (!$this->prettyErrorScreens) {
            return;
        }

        $view = '@ContaoCore/Error/'.$template.'.html.twig';
        $parameters = $this->getTemplateParameters($view, $statusCode, $event);

        try {
            $event->setResponse(new Response($this->twig->render($view, $parameters), $statusCode));
        } catch (Error $e) {
            $event->setResponse(new Response($this->twig->render('@ContaoCore/Error/error.html.twig'), 500));
        }
    }

    /**
     * @return array<string,string|int>
     */
    private function getTemplateParameters(string $view, int $statusCode, ExceptionEvent $event): array
    {
        $this->framework->initialize(true);

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);
        $encoded = StringUtil::encodeEmail($config->get('adminEmail'));

        return [
            'statusCode' => $statusCode,
            'statusName' => Response::$statusTexts[$statusCode],
            'template' => $view,
            'base' => $event->getRequest()->getBasePath(),
            'language' => $event->getRequest()->getLocale(),
            'adminEmail' => '&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$encoded,
            'exception' => $event->getThrowable()->getMessage(),
        ];
    }

    private function getStatusCodeForException(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return (int) $exception->getStatusCode();
        }

        return 500;
    }
}
