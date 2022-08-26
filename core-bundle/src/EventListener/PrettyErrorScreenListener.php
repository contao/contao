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
use Contao\CoreBundle\Exception\RouteParametersException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Twig\Error\Error;

/**
 * @internal
 */
class PrettyErrorScreenListener
{
    private bool $prettyErrorScreens;
    private Environment $twig;
    private ContaoFramework $framework;
    private Security $security;
    private PageRegistry $pageRegistry;
    private HttpKernelInterface $httpKernel;

    public function __construct(bool $prettyErrorScreens, Environment $twig, ContaoFramework $framework, Security $security, PageRegistry $pageRegistry, HttpKernelInterface $httpKernel)
    {
        $this->prettyErrorScreens = $prettyErrorScreens;
        $this->twig = $twig;
        $this->framework = $framework;
        $this->security = $security;
        $this->pageRegistry = $pageRegistry;
        $this->httpKernel = $httpKernel;
    }

    /**
     * Map an exception to an error screen.
     */
    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
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
                $this->renderErrorScreenByType(503, $event);

                if (!$event->hasResponse()) {
                    $this->renderTemplate('service_unavailable', 503, $event);
                }
                break;

            default:
                $this->renderErrorScreenByException($event);
        }
    }

    private function renderBackendException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof RouteParametersException) {
            $this->renderTemplate('missing_route_parameters', 501, $event);

            return;
        }

        $this->renderTemplate('backend', $this->getStatusCodeForException($exception), $event);
    }

    private function renderErrorScreenByType(int $type, ExceptionEvent $event): void
    {
        static $processing;

        if (true === $processing) {
            return;
        }

        $processing = true;

        try {
            $this->framework->initialize(true);

            $request = $event->getRequest();
            $pageModel = $request->attributes->get('pageModel');

            if (!$pageModel instanceof PageModel) {
                return;
            }

            $pageAdapter = $this->framework->getAdapter(PageModel::class);
            $errorPage = $pageAdapter->findFirstPublishedByTypeAndPid('error_'.$type, $pageModel->loadDetails()->rootId);

            if (null === $errorPage) {
                return;
            }

            $errorPage->loadDetails();
            $errorPage->protected = false;

            $route = $this->pageRegistry->getRoute($errorPage);
            $subRequest = $request->duplicate(null, null, $route->getDefaults());

            try {
                $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
                $event->setResponse($response);
            } catch (ResponseException $e) {
                $event->setResponse($e->getResponse());
            } catch (\Throwable $e) {
                $event->setThrowable($e);
            }
        } finally {
            $processing = false;
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
     * @return array<string,mixed>
     */
    private function getTemplateParameters(string $view, int $statusCode, ExceptionEvent $event): array
    {
        $this->framework->initialize();

        $config = $this->framework->getAdapter(Config::class);
        $encoded = StringUtil::encodeEmail($config->get('adminEmail'));

        try {
            $isBackendUser = $this->security->isGranted('ROLE_USER');
        } catch (AuthenticationCredentialsNotFoundException $e) {
            $isBackendUser = false;
        }

        return [
            'statusCode' => $statusCode,
            'statusName' => Response::$statusTexts[$statusCode],
            'template' => $view,
            'base' => $event->getRequest()->getBasePath(),
            'language' => LocaleUtil::formatAsLanguageTag($event->getRequest()->getLocale()),
            'adminEmail' => '&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$encoded,
            'isBackendUser' => $isBackendUser,
            'exception' => $event->getThrowable()->getMessage(),
            'throwable' => $event->getThrowable(),
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
