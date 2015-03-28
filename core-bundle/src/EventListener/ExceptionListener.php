<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\NotFoundHttpException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Exception\ResponseExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Handle exceptions and create a proper response containing the error screen when debug mode is not active.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ExceptionListener
{
    /**
     * @var bool
     */
    private $renderErrorScreens;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Lookup map of all known exception templates in this handler.
     *
     * @var array
     */
    private static $exceptionTemplates = [
        'Contao\CoreBundle\Exception\AccessDeniedHttpException'          => 'be_forbidden',
        'Contao\CoreBundle\Exception\BadRequestTokenException'           => 'be_referer',
        'Contao\CoreBundle\Exception\ForwardPageNotFoundHttpException'   => 'be_no_forward',
        'Contao\CoreBundle\Exception\IncompleteInstallationException'    => 'be_incomplete',
        'Contao\CoreBundle\Exception\InsecureInstallationException'      => 'be_insecure',
        'Contao\CoreBundle\Exception\MaintenanceModeActiveHttpException' => 'be_unavailable',
        'Contao\CoreBundle\Exception\NoLayoutException'                  => 'be_no_layout',
        'Contao\CoreBundle\Exception\NoPagesFoundHttpException'          => 'be_no_active',
        'Contao\CoreBundle\Exception\NotFoundHttpException'              => 'be_no_page',
        'Contao\CoreBundle\Exception\RootNotFoundHttpException'          => 'be_no_root',
    ];

    /**
     * Create a new instance.
     *
     * @param bool   $renderErrorScreens Flag if the error screens shall be rendered.
     * @param string $rootDir            The kernel root directory for reading the templates
     *                                   (only applicable when renderErrorScreens is true).
     */
    public function __construct($renderErrorScreens, $rootDir)
    {
        $this->renderErrorScreens = $renderErrorScreens;
        $this->rootDir = dirname($rootDir);
    }

    /**
     * Forwards the request to the Frontend class if there is a page object.
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        // Search if an response is somewhere in the exception list.
        $response = $this->checkResponseException($event->getException());
        if ($response instanceof Response) {
            $event->setResponse($this->setXStatusCode($response));

            return;
        }

        // FIXME: log the exception here if we do not find a better place to log via monolog from outside.

        if (!$this->renderErrorScreens) {
            return;
        }

        // Search if any exception in the chain is implementing a http exception.
        $response = $this->checkHttpExceptions($exception);
        if ($response instanceof Response) {
            $event->setResponse($this->setXStatusCode($response));

            return;
        }

        // If still nothing worked out, we wrap it in a plain "internal error occured" message.
        $event->setResponse($this->setXStatusCode(new Response($this->renderErrorTemplate('be_error'), 500)));
    }

    /**
     * Ensure we have a proper X-Status-Code set on a response.
     *
     * Setting the status code is needed as otherwise the kernel will not know we handled the exception and will set
     * an status code of 500 ignoring the status of our response.
     *
     * @param Response $response
     *
     * @return Response
     */
    private function setXStatusCode(Response $response)
    {
        if (!$response->headers->has('X-Status-Code')) {
            $response->headers->set('X-Status-Code', $response->getStatusCode());
        }

        return $response;
    }

    /**
     * Check if the exception or any exception in the chain implements the response exception interface.
     *
     * @param \Exception $exception The exception to walk on.
     *
     * @return null|Response
     */
    private function checkResponseException($exception)
    {
        do {
            if ($exception instanceof ResponseExceptionInterface) {
                return $exception->getResponse();
            }
        } while (null !== ($exception = $exception->getPrevious()));

        return null;
    }

    /**
     * Check if the exception or any exception in the chain inherits from HttpException and is understood by us.
     *
     * @param \Exception $exception The exception to walk on.
     *
     * @return null|Response
     */
    private function checkHttpExceptions(\Exception $exception)
    {
        do {
            if ($response = $this->checkHttpException($exception)) {
                return $response;
            }
        } while (null !== ($exception = $exception->getPrevious()));

        return null;
    }

    /**
     * Try to render a Contao 404 page first.
     *
     * @param \Exception $exception The exception to check.
     *
     * @return null|Response
     *
     * @throws \Exception When anything went really haywire during rendering of the page.
     */
    private function tryToRenderContao404($exception)
    {
        // FIXME: I guess it is hopeless to render a 404 page from within a root page when no root page is published.
        // We might want to skip RootNotFoundHttpException then and also NoPagesFoundHttpException is an candidate.

        if ($exception instanceof NotFoundHttpException) {
            static $processing;

            if (isset($GLOBALS['TL_PTY']['error_404']) && class_exists($GLOBALS['TL_PTY']['error_404'])) {
                if ($processing) {
                    return null;
                }

                // Prevent entering this method multiple times causing endless loop.
                $processing = true;

                try {
                    // FIXME: introduce some contao_frontend_404 route and do a subrequest on it might be more sufficient.
                    /** @var \PageError404 $pageHandler */
                    $pageHandler = new $GLOBALS['TL_PTY']['error_404']();
                    $response    = $pageHandler->getResponse(false);
                    $processing  = false;

                    return $response;
                } catch (ResponseException $pageException) {
                    $processing = false;

                    return $pageException->getResponse();
                } catch (NotFoundHttpException $pageException) {
                    $processing = false;

                    // We can safely assume that when we get an not found exception when trying to render a not found
                    // page, that we have to render the internal page.
                    return null;
                } catch (\Exception $pageException) {
                    $processing = false;
                    // Throwing the page exception here is intentional as we have some error handler problems!
                    // We should never end up here.
                    // Fixing error handling code paths is more important than the original exception.
                    throw $pageException;
                }
            }
        }

        return null;
     }

    /**
     * Check if the exception is understood by us.
     *
     * @param \Exception $exception The exception to walk on.
     *
     * @return null|Response
     */
    private function checkHttpException($exception)
    {
        if (!$exception instanceof HttpExceptionInterface) {
            return null;
        }

        $response = $this->tryToRenderContao404($exception);
        if ($response) {
            return $response;
        }

        // Determine if the class or any of the parents is known by us.
        $candidates = array_intersect(
            array_merge([get_class($exception)], class_parents($exception)),
            array_keys(self::$exceptionTemplates)
        );
        if (!empty($candidates)) {
            $template = self::$exceptionTemplates[array_shift($candidates)];
            return $this->createTemplateResponseFromException($template, $exception);
        }

        // Unknown HttpExceptionInterface implementing exception, no way to handle.
        return null;
    }

    /**
     * Try to create a response for the given template.
     *
     * @param string                 $template  The name of the template to render.
     * @param HttpExceptionInterface $exception The exception to render.
     *
     * @return Response
     */
    private function createTemplateResponseFromException($template, HttpExceptionInterface $exception)
    {
        return new Response(
            $this->renderErrorTemplate($template),
            $exception->getStatusCode(),
            $exception->getHeaders()
        );
    }

    /**
     * Try to render an error template.
     *
     * @param string $template The template name. Will get searched at standard locations.
     *
     * @return string
     */
    private function renderErrorTemplate($template)
    {
        // TODO: make twig templates out of these.
        if ($response = $this->tryReadTemplate(sprintf('%s/templates/%s.html5', $this->rootDir, $template))) {
            return $response;
        }

        return $this->tryReadTemplate(
            sprintf('%s/../Resources/contao/templates/backend/%s.html5', __DIR__, $template)
        );
    }

    /**
     * Try to read a template.
     *
     * @param string $template The file path to test.
     *
     * @return null|string
     */
    private function tryReadTemplate($template)
    {
        if (file_exists($template)) {
            // Isolate the template parsing, the "unused" root dir variable will get used in the template.
            $isolatedRun = function ($template, $rootDir) {
                ob_start();
                include $template;

                return ob_get_clean();
            };

            return $isolatedRun($template, $this->rootDir);
        }

        return null;
    }
}
