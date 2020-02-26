<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Response\InitializeControllerResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Custom controller to support legacy entry point scripts.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @deprecated Deprecated in Contao 4.0, to be removed in Contao 5.0
 */
class InitializeController extends Controller
{
    /**
     * Initializes the Contao framework.
     *
     * @return InitializeControllerResponse
     *
     * @Route("/_contao/initialize", name="contao_initialize")
     */
    public function indexAction()
    {
        @trigger_error('Custom entry points are deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        $masterRequest = $this->get('request_stack')->getMasterRequest();
        $realRequest = Request::createFromGlobals();
        $realRequest->setSession($masterRequest->getSession());
        $realRequest->setLocale($masterRequest->getLocale());

        // Necessary to generate the correct base path
        foreach (['REQUEST_URI', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'PHP_SELF'] as $name) {
            $realRequest->server->set(
                $name,
                str_replace(TL_SCRIPT, 'app.php', $realRequest->server->get($name))
            );
        }

        $realRequest->attributes->replace($masterRequest->attributes->all());

        // Empty the request stack to make our real request the master
        do {
            $pop = $this->get('request_stack')->pop();
        } while ($pop);

        // Initialize the framework with the real request
        $this->get('request_stack')->push($realRequest);
        $this->get('contao.framework')->initialize();

        // Add the master request again. When Kernel::handle() is finished,
        // it will pop the current request, resulting in the real request being active.
        $this->get('request_stack')->push($masterRequest);

        set_exception_handler(function ($e) use ($realRequest) {
            // Do not catch PHP7 Throwables
            if (!$e instanceof \Exception) {
                throw $e;
            }

            $this->handleException($e, $realRequest, HttpKernelInterface::MASTER_REQUEST);
        });

        // Collect all output into final response
        $response = new Response();
        ob_start(
            function($buffer) use ($response) {
                $response->setContent($response->getContent().$buffer);

                return '';
            }
        );

        // register_shutdown_function() somehow can't handle $this
        $self = $this;
        register_shutdown_function(
            function() use ($self, $realRequest, $response) {
                $self->handleResponse($realRequest, $response, KernelInterface::MASTER_REQUEST);
            }
        );

        return new InitializeControllerResponse('', 204);
    }

    /**
     * Handles an exception by trying to convert it to a Response object.
     *
     * @param int $type HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST
     *
     * @see HttpKernel::handleException()
     */
    private function handleException(\Exception $e, Request $request, $type)
    {
        $event = new GetResponseForExceptionEvent($this->get('http_kernel'), $request, $type, $e);
        $this->get('event_dispatcher')->dispatch(KernelEvents::EXCEPTION, $event);

        // A listener might have replaced the exception
        $e = $event->getException();

        if (!$event->hasResponse()) {
            throw $e;
        }

        $response = $event->getResponse();

        // The developer asked for a specific status code
        if ($response->headers->has('X-Status-Code')) {
            @trigger_error(sprintf('Using the X-Status-Code header is deprecated since Symfony 3.3 and will be removed in 4.0. Use %s::allowCustomResponseCode() instead.', GetResponseForExceptionEvent::class), E_USER_DEPRECATED);

            $response->setStatusCode($response->headers->get('X-Status-Code'));
            $response->headers->remove('X-Status-Code');
        } elseif (
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
            $event = new FilterResponseEvent($this->get('http_kernel'), $request, $type, $response);
            $this->get('event_dispatcher')->dispatch(KernelEvents::RESPONSE, $event);
            $response = $event->getResponse();

            $this->get('event_dispatcher')->dispatch(
                KernelEvents::FINISH_REQUEST,
                new FinishRequestEvent($this->get('http_kernel'), $request, $type)
            );

            $this->get('request_stack')->pop();
        } catch (\Exception $e) {
            // ignore and continue with original response
        }

        $response->send();

        $kernel = $this->get('kernel');

        if ($kernel instanceof TerminableInterface) {
            $kernel->terminate($request, $response);
        }

        exit;
    }

    /**
     * Execute kernel.response and kernel.finish_request events
     */
    private function handleResponse(Request $request, Response $response, $type)
    {
        if (!headers_sent()) {
            $response->headers->replace([]);

            foreach (headers_list() as $header) {
                list($name, $value) = explode(':', $header, 2);
                $response->headers->set($name, $value, false);
            }

            header_remove();
        }

        @ob_end_clean();

        $event = new FilterResponseEvent($this->get('http_kernel'), $request, $type, $response);

        try {
            $this->get('event_dispatcher')->dispatch(KernelEvents::RESPONSE, $event);
        } catch (\Throwable $e) {
            // Ignore any errors from events
        }

        $this->get('event_dispatcher')->dispatch(
            KernelEvents::FINISH_REQUEST,
            new FinishRequestEvent($this->get('http_kernel'), $request, $type)
        );
        $this->get('request_stack')->pop();

        $response = $event->getResponse();
        $response->send();

        $kernel = $this->get('kernel');

        if ($kernel instanceof TerminableInterface) {
            $kernel->terminate($request, $response);
        }
    }
}
