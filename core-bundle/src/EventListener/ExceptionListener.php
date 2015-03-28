<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\ForwardPageNotFoundHttpException;
use Contao\CoreBundle\Exception\NoPagesFoundHttpException;
use Contao\CoreBundle\Exception\NotFoundHttpException;
use Contao\CoreBundle\Exception\ResponseExceptionInterface;
use Contao\CoreBundle\Exception\RootNotFoundHttpException;
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
     * @var string
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
            $event->setResponse($response);

            return;
        }

        // FIXME: log the exception here if we do not find a better place to log via monolog from outside.

        if (!$this->renderErrorScreens) {
            return;
        }

        // Search if any exception in the chain is implementing a http exception.
        $response = $this->checkHttpExceptions($exception);
        if ($response instanceof Response) {
            $event->setResponse($response);

            return;
        }

        // If still nothing worked out, we wrap it in a plain "internal error occured" message.
        $event->setResponse(
            new Response($this->renderErrorTemplate('be_error', 'An error occurred while executing this script!'), 500)
        );
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
     * Check if the exception is understood by us.
     *
     * @param \Exception $exception The exception to walk on.
     *
     * @return null|Response
     */
    private function checkHttpException($exception)
    {
        if ($exception instanceof NotFoundHttpException
            || $exception instanceof ForwardPageNotFoundHttpException
            || $exception instanceof RootNotFoundHttpException
            || $exception instanceof NoPagesFoundHttpException
        ) {
            // TODO: try to handle these via rendering a 404 page first.
        }

        if ($exception instanceof HttpExceptionInterface) {
            $candidates = array_intersect(class_parents($exception), array_keys(self::$exceptionTemplates));
            if (!empty($candidates)) {
                $template = self::$exceptionTemplates[$candidates[0]];
                return $this->createTemplateResponseFromException($template, $exception);
            }
        }

        // Not one of the default exceptions, do not handle it.
        // Exception not understood.
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
            $this->renderErrorTemplate(
                $template,
                $exception->getMessage()
            ),
            $exception->getStatusCode(),
            $exception->getHeaders()
        );
    }

    /**
     * Try to render an error template.
     *
     * @param string $template        The template name. Will get searched at standard locations.
     * @param string $fallbackMessage The fallback message to display when no template has been found.
     *
     * @return string
     */
    private function renderErrorTemplate($template, $fallbackMessage = '')
    {
        // TODO: make twig templates out of these.
        if ($response = $this->tryReadTemplate(sprintf('%s/templates/%s.html5', $this->rootDir, $template))) {
            return $response;
        } elseif ($response = $this->tryReadTemplate(
            sprintf('%s/../Resources/contao/templates/backend/%s.html5', __DIR__, $template)
        )) {
            return $response;
        }

        return $fallbackMessage;
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
            // Isolate the template parsing, the root dir will get used in the template.
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
