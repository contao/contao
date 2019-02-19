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

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageError404;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PrettyErrorScreenListener
{
    /**
     * @var bool
     */
    private $prettyErrorScreens;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private static $mapper = [
        ForwardPageNotFoundException::class => 'forward_page_not_found',
        IncompleteInstallationException::class => 'incomplete_installation',
        InsecureInstallationException::class => 'insecure_installation',
        InvalidRequestTokenException::class => 'invalid_request_token',
        NoActivePageFoundException::class => 'no_active_page_found',
        NoLayoutSpecifiedException::class => 'no_layout_specified',
        NoRootPageFoundException::class => 'no_root_page_found',
    ];

    public function __construct(bool $prettyErrorScreens, \Twig_Environment $twig, ContaoFramework $framework, TokenStorageInterface $tokenStorage, LoggerInterface $logger = null)
    {
        $this->prettyErrorScreens = $prettyErrorScreens;
        $this->twig = $twig;
        $this->framework = $framework;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * Map an exception to an error screen.
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
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

    private function handleException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        switch (true) {
            case $this->isBackendUser():
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

    private function renderBackendException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        $this->logException($exception);
        $this->renderTemplate('backend', $this->getStatusCodeForException($exception), $event);
    }

    private function renderErrorScreenByType(int $type, GetResponseForExceptionEvent $event): void
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

        $type = 'error_'.$type;

        if (!isset($GLOBALS['TL_PTY'][$type]) || !class_exists($GLOBALS['TL_PTY'][$type])) {
            return null;
        }

        /** @var PageError404 $pageHandler */
        $pageHandler = new $GLOBALS['TL_PTY'][$type]();

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
    private function renderErrorScreenByException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();
        $statusCode = $this->getStatusCodeForException($exception);
        $template = null;

        $this->logException($exception);

        // Look for a template
        do {
            if ($exception instanceof \Exception) {
                $template = $this->getTemplateForException($exception);
            }
        } while (null === $template && null !== ($exception = $exception->getPrevious()));

        $this->renderTemplate($template ?: 'error', $statusCode, $event);
    }

    private function getTemplateForException(\Exception $exception): ?string
    {
        foreach (self::$mapper as $class => $template) {
            if ($exception instanceof $class) {
                return $template;
            }
        }

        return null;
    }

    private function renderTemplate(string $template, int $statusCode, GetResponseForExceptionEvent $event): void
    {
        if (!$this->prettyErrorScreens) {
            return;
        }

        $view = '@ContaoCore/Error/'.$template.'.html.twig';
        $parameters = $this->getTemplateParameters($view, $statusCode, $event);

        try {
            $event->setResponse(new Response($this->twig->render($view, $parameters), $statusCode));
        } catch (\Twig_Error $e) {
            $event->setResponse(new Response($this->twig->render('@ContaoCore/Error/error.html.twig'), 500));
        }
    }

    /**
     * @return array<string,string|int>
     */
    private function getTemplateParameters(string $view, int $statusCode, GetResponseForExceptionEvent $event): array
    {
        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);
        $encoded = StringUtil::encodeEmail($config->get('adminEmail'));

        return [
            'statusCode' => $statusCode,
            'statusName' => Response::$statusTexts[$statusCode],
            'template' => $view,
            'base' => $event->getRequest()->getBasePath(),
            'adminEmail' => '&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$encoded,
            'exception' => $event->getException()->getMessage(),
        ];
    }

    private function logException(\Exception $exception): void
    {
        if (Kernel::VERSION_ID >= 40100 || null === $this->logger || !$this->isLoggable($exception)) {
            return;
        }

        $this->logger->critical('An exception occurred.', ['exception' => $exception]);
    }

    private function isLoggable(\Exception $exception): bool
    {
        do {
            if ($exception instanceof ForwardPageNotFoundException) {
                return true;
            }

            if (isset(self::$mapper[\get_class($exception)])) {
                return false;
            }
        } while (null !== ($exception = $exception->getPrevious()));

        return true;
    }

    private function isBackendUser(): bool
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return false;
        }

        $user = $token->getUser();

        if (null === $user) {
            return false;
        }

        return $user instanceof BackendUser;
    }

    private function getStatusCodeForException(\Exception $exception): int
    {
        if ($exception instanceof HttpException) {
            return (int) $exception->getStatusCode();
        }

        return 500;
    }
}
