<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Adapter\ConfigAdapter;
use Contao\CoreBundle\Exception\NoPagesFoundHttpException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Exception\ResponseExceptionInterface;
use Contao\CoreBundle\Exception\RootNotFoundHttpException;
use Contao\String;
use Contao\System;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles exceptions and creates a proper response with an error screen.
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
     * @var TwigEngine
     */
    private $twig;

    /**
     * @var ConfigAdapter
     */
    private $config;

    /**
     * @var array
     */
    private static $exceptionTemplates = [
        'Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException' => 'forbidden',
        'Contao\\CoreBundle\\Exception\\BadRequestTokenHttpException'          => 'referer',
        'Contao\\CoreBundle\\Exception\\ForwardPageNotFoundHttpException'      => 'no_forward',
        'Contao\\CoreBundle\\Exception\\IncompleteInstallationHttpException'   => 'incomplete',
        'Contao\\CoreBundle\\Exception\\InsecureInstallationHttpException'     => 'insecure',
        'Contao\\CoreBundle\\Exception\\MaintenanceModeActiveHttpException'    => 'unavailable',
        'Contao\\CoreBundle\\Exception\\NoLayoutHttpException'                 => 'no_layout',
        'Contao\\CoreBundle\\Exception\\NoPagesFoundHttpException'             => 'no_active',
        'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException'     => 'no_page',
        'Contao\\CoreBundle\\Exception\\RootNotFoundHttpException'             => 'no_root',
    ];

    /**
     * Constructor.
     *
     * @param bool          $renderErrorScreens True to render the error screens
     * @param TwigEngine    $twig               The twig rendering engine
     * @param ConfigAdapter $config             The config adapter object
     */
    public function __construct($renderErrorScreens, TwigEngine $twig, ConfigAdapter $config)
    {
        $this->renderErrorScreens = $renderErrorScreens;
        $this->twig               = $twig;
        $this->config             = $config;
    }

    /**
     * Tries to handle the exception and creates a response for known exception types.
     *
     * @param GetResponseForExceptionEvent $event The event object
     *
     * @todo Log the exception here if we do not find a better place to log via Monolog from outside.
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        // Search a response in the exception list
        $response = $this->checkResponseException($event->getException());

        if ($response instanceof Response) {
            $event->setResponse($this->setXStatusCode($response));

            return;
        }

        if (!$this->renderErrorScreens) {
            return;
        }

        // Search an HTTP exception in the chain
        $response = $this->checkHttpExceptions($exception, $event->getRequest());

        if ($response instanceof Response) {
            $event->setResponse($this->setXStatusCode($response));

            return;
        }

        // Return a plain "internal error" response
        $event->setResponse($this->renderErrorTemplate('error', 500, $event->getRequest()));
    }

    /**
     * Ensures that we have a proper X-Status-Code set on a response.
     *
     * Setting the status code is needed as otherwise the kernel will not know we handled the
     * exception and will set an status code of 500 ignoring the status of our response.
     *
     * @param Response $response The response object
     *
     * @return Response The response object
     */
    private function setXStatusCode(Response $response)
    {
        if (!$response->headers->has('X-Status-Code')) {
            $response->headers->set('X-Status-Code', $response->getStatusCode());
        }

        return $response;
    }

    /**
     * Checks if any exception in the chain implements the response exception interface.
     *
     * @param \Exception $exception The exception object
     *
     * @return Response|null The response object or null
     */
    private function checkResponseException(\Exception $exception)
    {
        do {
            if ($exception instanceof ResponseExceptionInterface) {
                return $exception->getResponse();
            }
        } while (null !== ($exception = $exception->getPrevious()));

        return null;
    }

    /**
     * Checks if any exception in the chain implements the HttpExceptionInterface interface.
     *
     * @param \Exception $exception The exception object
     * @param Request    $request   The request object
     *
     * @return Response|null The response object or null
     */
    private function checkHttpExceptions(\Exception $exception, Request $request)
    {
        do {
            if (null !== ($response = $this->checkHttpException($exception, $request))) {
                return $response;
            }
        } while (null !== ($exception = $exception->getPrevious()));

        return null;
    }

    /**
     * Checks whether we can handle the exception.
     *
     * @param \Exception $exception The exception object
     * @param Request    $request   The HTTP request.
     *
     * @return Response|null The response object or null
     */
    private function checkHttpException(\Exception $exception, Request $request)
    {
        if (!$exception instanceof HttpExceptionInterface) {
            return null;
        }

        if (null !== ($response = $this->tryToRenderContao404($exception))) {
            return $response;
        }

        // Determine if the class or any of its parents is known to us
        $candidates = array_intersect(
            array_merge([get_class($exception)], class_parents($exception)),
            array_keys(self::$exceptionTemplates)
        );

        if (empty($candidates)) {
            return null;
        }

        $template = self::$exceptionTemplates[array_shift($candidates)];

        return $this->createTemplateResponseFromException($template, $exception, $request);
    }

    /**
     * Tries to render a Contao 404 page.
     *
     * @param \Exception $exception The exception object
     *
     * @return Response|null The response object or null
     *
     * @throws \Exception When anything went really haywire during rendering of the page.
     */
    private function tryToRenderContao404(\Exception $exception)
    {
        if (!$this->exceptionAllowsToRenderContao404($exception)) {
            return null;
        }

        static $processing;

        if (true === $processing) {
            return null;
        }

        $processing = true;

        try {
            /** @var \PageError404 $pageHandler */
            $pageHandler = new $GLOBALS['TL_PTY']['error_404']();
            // FIXME: introducing a contao_frontend_404 route and doing a subrequest on it might be more efficient
            $response    = $pageHandler->getResponse(false);
            $processing  = false;

            return $response;
        } catch (ResponseException $pageException) {
            $processing = false;

            return $pageException->getResponse();
        } catch (NotFoundHttpException $pageException) {
            $processing = false;

            // Render the internal page if we get a not found exception while trying to render the not found page
            return null;
        } catch (\Exception $pageException) {
            $processing = false;

            // Throw the page exception if we should ever get here
            throw $pageException;
        }
     }

    /**
     * Checks if the Contao 404 page can be rendered for a particular exception.
     *
     * @param \Exception $exception The exception object
     *
     * @return bool True if the Contao 404 page can be rendered
     */
    private function exceptionAllowsToRenderContao404(\Exception $exception)
    {
        // Not a 404 exception or the frontend has not been booted
        if (!$exception instanceof NotFoundHttpException || !defined('BE_USER_LOGGED_IN')) {
            return false;
        }

        // No root page or no published page at all
        if ($exception instanceof RootNotFoundHttpException || $exception instanceof NoPagesFoundHttpException) {
            return false;
        }

        // No 404 page handler has been registered
        if (!isset($GLOBALS['TL_PTY']['error_404']) || !class_exists($GLOBALS['TL_PTY']['error_404'])) {
            return false;
        }

        return true;
    }

    /**
     * Returns a response for the given template.
     *
     * @param string                 $template  The name of the template
     * @param HttpExceptionInterface $exception The exception object
     * @param Request                $request   The request object
     *
     * @return Response The response object
     */
    private function createTemplateResponseFromException($template, HttpExceptionInterface $exception, Request $request)
    {
        $response = $this->renderErrorTemplate($template, $exception->getStatusCode(), $request);
        $response->headers->add($exception->getHeaders());

        return $response;
    }

    /**
     * Loads the language strings.
     *
     * @return array|null The language strings or null
     *
     * @internal
     */
    protected function loadLanguageStrings()
    {
        if (!class_exists('Contao\\System')) {
            return null;
        }

        System::loadLanguageFile('exception');

        if (!isset($GLOBALS['TL_LANG']['XPT'])) {
            return null;
        }

        return $GLOBALS['TL_LANG']['XPT'];
    }

    /**
     * Returns the template parameters.
     *
     * @param string  $view       The name of the view
     * @param int     $statusCode The HTTP status code
     * @param Request $request    The request object
     *
     * @return array|null The template parameters or null
     */
    private function getTemplateParameters($view, $statusCode, Request $request)
    {
        $languageStrings = $this->loadLanguageStrings();

        if (null === $languageStrings) {
            return null;
        }

        return [
            'statusCode' => $statusCode,
            'error'      => $languageStrings,
            'template'   => $view,
            'adminEmail' => String::encodeEmail('mailto:' . $this->config->get('adminEmail')),
            'base'       => $request->getBasePath(),
        ];
    }

    /**
     * Returns the default template parameters in case the Contao framework cannot be booted.
     *
     * @param string $view       The name of the view
     * @param int    $statusCode The HTTP status code
     *
     * @return array The template parameters
     */
    private function getFallbackTemplateParameters($view, $statusCode)
    {
        return [
            'statusCode'        => $statusCode,
            'error'             => [
                'error'         => 'An error occurred',
                'matter'        => 'What\'s the matter?',
                'errorOccurred' => 'An error occurred while executing this script. Something does not work properly. Additionally an error occurred while trying to display the error message.',
                'howToFix'      => 'How can I fix the issue?',
                'errorFixOne'   => 'Open the <code>app/logs/error.log</code> file and find the associated error message (usually the last one).',
                'more'          => 'Tell me more, please',
                'errorExplain'  => 'The script execution stopped, because something does not work properly. The actual error message is hidden by this notice for security reasons and can be found in in the <code>app/logs/error.log</code> file (see above). If you do not understand the error message or do not know how to fix the problem, search the <a href="https://contao.org/faq.html" target="_blank">Contao FAQs</a> or visit the <a href="https://contao.org/support.html" target="_blank">Contao support page</a>.',
            ],
            'template'          => $view,
            'adminEmail'        => '',
            'base'              => '',
            'agentClass'        => '', // FIXME: there is no agentClass in the getTemplateParameters() method?
        ];
    }

    /**
     * Renders an error template and returns the response object.
     *
     * @param string  $template   The template name
     * @param int     $statusCode The HTTP status code
     * @param Request $request    The request object
     *
     * @return Response The response object
     */
    private function renderErrorTemplate($template, $statusCode, Request $request)
    {
        $view = "@ContaoCore/Error/$template.html.twig";

        if (!$this->twig->exists($view)) {
            $view       = '@ContaoCore/Error/error.html.twig';
            $statusCode = 500;
        }

        $parameters = $this->getTemplateParameters($view, $statusCode, $request);

        if (null === $parameters) {
            $view       = '@ContaoCore/Error/error.html.twig';
            $statusCode = 500;
            $parameters = $this->getFallbackTemplateParameters($view, $statusCode);
        }

        return $this->setXStatusCode($this->twig->renderResponse($view, $parameters)->setStatusCode($statusCode));
    }
}
