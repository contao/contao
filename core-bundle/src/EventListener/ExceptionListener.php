<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Exception\NoPagesFoundHttpException;
use Contao\CoreBundle\Exception\NotFoundHttpException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Exception\ResponseExceptionInterface;
use Contao\CoreBundle\Exception\RootNotFoundHttpException;
use Contao\Environment;
use Contao\String;
use Contao\System;
use Symfony\Bundle\TwigBundle\TwigEngine;
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
     * @var TwigEngine
     */
    private $twig;

    /**
     * Lookup map of all known exception templates in this handler.
     *
     * @var array
     */
    private static $exceptionTemplates = [
        'Contao\CoreBundle\Exception\AccessDeniedHttpException'           => 'forbidden',
        'Contao\CoreBundle\Exception\BadRequestTokenHttpException'        => 'referer',
        'Contao\CoreBundle\Exception\ForwardPageNotFoundHttpException'    => 'no_forward',
        'Contao\CoreBundle\Exception\IncompleteInstallationHttpException' => 'incomplete',
        'Contao\CoreBundle\Exception\InsecureInstallationHttpException'   => 'insecure',
        'Contao\CoreBundle\Exception\MaintenanceModeActiveHttpException'  => 'unavailable',
        'Contao\CoreBundle\Exception\NoLayoutHttpException'               => 'no_layout',
        'Contao\CoreBundle\Exception\NoPagesFoundHttpException'           => 'no_active',
        'Contao\CoreBundle\Exception\NotFoundHttpException'               => 'no_page',
        'Contao\CoreBundle\Exception\RootNotFoundHttpException'           => 'no_root',
    ];

    /**
     * Constructor.
     *
     * @param bool       $renderErrorScreens Flag if the error screens shall be rendered.
     * @param TwigEngine $twig               The twig rendering engine.
     */
    public function __construct($renderErrorScreens, TwigEngine $twig)
    {
        $this->renderErrorScreens = $renderErrorScreens;
        $this->twig = $twig;
    }

    /**
     * Try to handle the exception and create a response for known exception types.
     *
     * If the listener is configured to display the pretty error screens, it will do so.
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
        $event->setResponse($this->renderErrorTemplate('error', 500));
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
     * Check if the Contao 404 should be rendered.
     *
     * @param \Exception $exception The exception to test.
     *
     * @return bool
     */
    private function isRenderingOfContao404PossibleFor($exception)
    {
        // If not an 404 exception or the frontend has not been booted, we can not render.
        if (!($exception instanceof NotFoundHttpException && defined('BE_USER_LOGGED_IN'))) {
            return false;
        }

        // It is hopeless to render a 404 page when no root page or no page at all is published.
        if ($exception instanceof RootNotFoundHttpException || $exception instanceof NoPagesFoundHttpException) {
            return false;
        }

        // If no 404 page handler has been registered, we also can not render.
        if (!(isset($GLOBALS['TL_PTY']['error_404']) && class_exists($GLOBALS['TL_PTY']['error_404']))) {
            return false;
        }

        return true;
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
        if (!$this->isRenderingOfContao404PossibleFor($exception)) {
            return null;
        }

        static $processing;
        // Exit when we are already trying to render a 404 page but an exception was thrown from within.
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
        $response = $this->renderErrorTemplate($template, $exception->getStatusCode());
        $response->headers->add($exception->getHeaders());

        return $response;
    }

    /**
     * Load the language strings
     *
     * @return array|null
     *
     * @internal
     *
     * FIXME: replace this with a real - non legacy - language string loader.
     */
    protected function loadLanguageStrings()
    {
        System::loadLanguageFile('exception');

        if (!isset($GLOBALS['TL_LANG']['XPT'])) {
            return null;
        }

        return $GLOBALS['TL_LANG']['XPT'];
    }

    /**
     * Retrieve the values for the template.
     *
     * @param string $view       The name of the view.
     *
     * @param int    $statusCode The HTTP status code.
     *
     * @return array|null
     */
    private function getTemplateParameters($view, $statusCode)
    {
        // System and String are safe to autoload, Environment and Config NOT.
        if (!(class_exists('Contao\\System', true)
            && class_exists('Contao\\String', true)
            && class_exists('Contao\\Environment', false)
            && class_exists('Contao\\Config', false)
        )) {
            return null;
        }

        $languageStrings = $this->loadLanguageStrings();

        if (null === $languageStrings) {
            return null;
        }

        return [
            'statusCode' => $statusCode,
            'error'      => $languageStrings,
            'template'   => $view,
            'agentClass' => Environment::get('agent')->class,
            'adminEmail' => String::encodeEmail('mailto:' . Config::get('adminEmail')),
            'base'       => Environment::get('base')
        ];
    }

    /**
     * Display the error screen as a last resort with embedded values as the system is not booted and nothing available.
     *
     * This should only happen on very rare occasions, i.e. when the configuration is really broken and there is an
     * error in booting the Contao framework, but it may happen.
     *
     * @param string $view       The name of the view.
     *
     * @param int    $statusCode The HTTP status code.
     *
     * @return array
     */
    private function lastResort($view, $statusCode)
    {
        return [
            'statusCode'        => $statusCode,
            'error'             => [
                'error'         => 'An error occurred',
                'matter'        => 'What\'s the matter?',
                'errorOccurred' => 'An error occurred while executing this script. Something does not work properly. Additionally an error occurred while trying to display the error message.',
                'howToFix'      => 'How can I fix the issue?',
                'errorFixOne'   => 'Open the &lt;code&gt;app/logs/error.log&lt;/code&gt; file and find the associated error message (usually the last one).',
                'more'          => 'Tell me more, please',
                'errorExplain'  => 'The script execution stopped, because something does not work properly. The actual error message is hidden by this notice for security reasons and can be found in in the <code>app/logs/error.log</code> file (see above). If you do not understand the error message or do not know how to fix the problem, search the <a href="https://contao.org/faq.html" target="_blank">Contao FAQs</a> or visit the <a href="https://contao.org/support.html" target="_blank">Contao support page</a>.',
            ],
            'template'          => $view,
            'agentClass'        => '',
            'adminEmail'        => '',
            'base'              => ''
        ];
    }

    /**
     * Try to render an error template.
     *
     * @param string $template   The template name. Will get searched at standard locations.
     *
     * @param int    $statusCode The HTTP status code to use in the response.
     *
     * @return Response
     */
    private function renderErrorTemplate($template, $statusCode)
    {
        $view = '@ContaoCore/Error/' . $template . '.html.twig';
        if (!$this->twig->exists($view)) {
            $view       = '@ContaoCore/Error/error.html.twig';
            $statusCode = 500;
        }

        $parameters = $this->getTemplateParameters($view, $statusCode);

        // Safety net - ensure that everything is available.
        if (null === $parameters) {
            $view       = '@ContaoCore/Error/error.html.twig';
            $statusCode = 500;
            $parameters = $this->lastResort($view, $statusCode);
        }

        return $this->setXStatusCode($this->twig->renderResponse($view, $parameters)->setStatusCode($statusCode));
    }
}
