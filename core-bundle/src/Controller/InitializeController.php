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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Response\InitializeControllerResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Custom controller to support legacy entry points.
 *
 * @internal
 *
 * @deprecated Deprecated in Contao 4.0, to be removed in Contao 5.0
 *
 * @Route("/_contao/initialize", name="contao_initialize")
 */
class InitializeController
{
    private ContaoFramework $framework;
    private RequestStack $requestStack;
    private EventDispatcherInterface $eventDispatcher;
    private HttpKernelInterface $httpKernel;
    private KernelInterface $kernel;

    public function __construct(ContaoFramework $framework, RequestStack $requestStack, EventDispatcherInterface $eventDispatcher, HttpKernelInterface $httpKernel, KernelInterface $kernel)
    {
        $this->framework = $framework;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
        $this->httpKernel = $httpKernel;
        $this->kernel = $kernel;
    }

    /**
     * Initializes the Contao framework.
     */
    public function __invoke(): InitializeControllerResponse
    {
        trigger_deprecation('contao/core-bundle', '4.0', 'Using custom entry points has been deprecated and will no longer work in Contao 5.0.');

        $mainRequest = $this->requestStack->getMainRequest();

        if (null === $mainRequest) {
            throw new \RuntimeException('The request stack did not contain a main request.');
        }

        $realRequest = Request::createFromGlobals();
        $realRequest->setLocale($mainRequest->getLocale());

        if ($mainRequest->hasSession()) {
            $realRequest->setSession($mainRequest->getSession());
        }

        if (!\defined('TL_SCRIPT')) {
            \define('TL_SCRIPT', '');
        }

        // Necessary to generate the correct base path
        foreach (['REQUEST_URI', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'PHP_SELF'] as $name) {
            $realRequest->server->set($name, str_replace(TL_SCRIPT, 'index.php', $realRequest->server->get($name)));
        }

        $realRequest->attributes->replace($mainRequest->attributes->all());

        // Empty the request stack to make our real request the main
        do {
            $pop = $this->requestStack->pop();
        } while ($pop);

        // Initialize the framework with the real request
        $this->requestStack->push($realRequest);
        $this->framework->initialize();

        // Add the main request again. When Kernel::handle() is finished,
        // it will pop the current request, resulting in the real request being active.
        $this->requestStack->push($mainRequest);

        set_exception_handler(
            function ($e) use ($realRequest): void {
                // Do not catch PHP7 Throwables
                if (!$e instanceof \Exception) {
                    throw $e;
                }

                $this->handleException($e, $realRequest);
            }
        );

        // Collect all output into final response
        $response = new Response();

        ob_start(
            static function ($buffer) use ($response) {
                $response->setContent($response->getContent().$buffer);

                return '';
            },
            0,
            PHP_OUTPUT_HANDLER_REMOVABLE | PHP_OUTPUT_HANDLER_CLEANABLE
        );

        // register_shutdown_function() somehow can't handle $this
        $self = $this;

        register_shutdown_function(
            static function () use ($self, $realRequest, $response): void {
                @ob_end_clean();
                $self->handleResponse($realRequest, $response);
            }
        );

        return new InitializeControllerResponse('', 204);
    }

    /**
     * Handles an exception by trying to convert it to a Response object.
     *
     * @see HttpKernel::handleException()
     */
    private function handleException(\Throwable $e, Request $request): void
    {
        $event = new ExceptionEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $e);
        $this->eventDispatcher->dispatch($event, KernelEvents::EXCEPTION);

        // A listener might have replaced the exception
        $e = $event->getThrowable();

        if (!$response = $event->getResponse()) {
            throw $e;
        }

        // The developer asked for a specific status code
        if (
            !$event->isAllowingCustomResponseCode()
            && !$response->isClientError()
            && !$response->isServerError()
            && !$response->isRedirect()
        ) {
            // Ensure that we actually have an error response
            if ($e instanceof HttpExceptionInterface) {
                // Keep the HTTP status code and headers
                $response->setStatusCode($e->getStatusCode());
                $response->headers->add($e->getHeaders());
            } else {
                $response->setStatusCode(500);
            }
        }

        try {
            $event = new ResponseEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
            $this->eventDispatcher->dispatch($event, KernelEvents::RESPONSE);
            $response = $event->getResponse();

            $this->eventDispatcher->dispatch(
                new FinishRequestEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST),
                KernelEvents::FINISH_REQUEST
            );

            $this->requestStack->pop();
        } catch (\Exception $e) {
            // ignore and continue with original response
        }

        $response->send();

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }

        exit;
    }

    /**
     * Execute kernel.response and kernel.finish_request events.
     */
    private function handleResponse(Request $request, Response $response): void
    {
        $event = new ResponseEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        try {
            $this->eventDispatcher->dispatch($event, KernelEvents::RESPONSE);
        } catch (\Throwable $e) {
            // Ignore any errors from events
        }

        $this->eventDispatcher->dispatch(
            new FinishRequestEvent($this->httpKernel, $request, HttpKernelInterface::MAIN_REQUEST),
            KernelEvents::FINISH_REQUEST
        );

        $this->requestStack->pop();

        $response = $event->getResponse();
        $response->send();

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }
    }
}
