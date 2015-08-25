<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Adapter\ConfigAdapter;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Renders pretty error screens for exceptions.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @internal
 */
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
     * @var ConfigAdapter
     */
    private $config;

    /**
     * @var GetResponseForExceptionEvent
     */
    private $event;

    /**
     * @var array
     */
    private $mapper = [
        'Contao\\CoreBundle\\Exception\\ForwardPageNotFoundException' => 'forward_page_not_found',
        'Contao\\CoreBundle\\Exception\\IncompleteInstallationException' => 'incomplete_installation',
        'Contao\\CoreBundle\\Exception\\InsecureInstallationException' => 'insecure_installation',
        'Contao\\CoreBundle\\Exception\\InvalidRequestTokenException' => 'invalid_request_token',
        'Contao\\CoreBundle\\Exception\\NoActivePageFoundException' => 'no_active_page_found',
        'Contao\\CoreBundle\\Exception\\NoLayoutSpecifiedException' => 'no_layout_specified',
        'Contao\\CoreBundle\\Exception\\NoRootPageFoundException' => 'no_root_page_found',
        'Contao\\CoreBundle\\Exception\\ServiceUnavailableException' => 'service_unavailable',
    ];

    /**
     * Constructor.
     *
     * @param bool              $prettyErrorScreens True to render the error screens
     * @param \Twig_Environment $twig               The twig environment
     * @param ConfigAdapter     $config             The config adapter
     */
    public function __construct($prettyErrorScreens, \Twig_Environment $twig, ConfigAdapter $config)
    {
        $this->prettyErrorScreens = $prettyErrorScreens;
        $this->twig = $twig;
        $this->config = $config;
    }

    /**
     * Map an exception to an error screen.
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->isMasterRequest() || 'html' !== $event->getRequest()->getRequestFormat()) {
            return;
        }

        $this->event = $event;
        $this->handleException($event->getException());
    }

    /**
     * Handles the exception.
     *
     * @param \Exception $exception The exception
     */
    private function handleException(\Exception $exception)
    {
        switch (true) {
            case $exception instanceof AccessDeniedHttpException:
                $this->renderErrorScreenByType(403);
                break;

            case $exception instanceof BadRequestHttpException:
            case $exception instanceof InternalServerErrorHttpException:
                $this->renderErrorScreenByException();
                break;

            case $exception instanceof NotFoundHttpException:
                $this->renderErrorScreenByType(404);
                break;

            case $exception instanceof ServiceUnavailableHttpException:
                $this->renderTemplate('service_unavailable', 503);
                break;

            default:
                $this->renderTemplate('error', 500);
                break;
        }
    }

    /**
     * Renders the error screen.
     *
     * @param int $type The error type
     */
    private function renderErrorScreenByType($type)
    {
        static $processing;

        if (true === $processing) {
            return;
        }

        $processing = true;

        if (null !== ($response = $this->getResponseFromPageHandler($type))) {
            $this->event->setResponse($response);
        }

        $processing = false;
    }

    /**
     * Returns the response of a Contao page handler.
     *
     * @param string $type The error type
     *
     * @return Response|null The response object or null
     */
    private function getResponseFromPageHandler($type)
    {
        $type = 'error_' . $type;

        if (!isset($GLOBALS['TL_PTY'][$type]) || !class_exists($GLOBALS['TL_PTY'][$type])) {
            return null;
        }

        /** @var \PageError404 $pageHandler */
        $pageHandler = new $GLOBALS['TL_PTY'][$type]();

        try {
            return $pageHandler->getResponse();
        } catch (RedirectResponseException $e) {
            return $e->getResponse();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Checks the exception chain for a known exception.
     */
    private function renderErrorScreenByException()
    {
        $statusCode = 500;
        $exception  = $this->event->getException();

        // Set the status code
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
        }

        // Look for a template
        do {
            $template = $this->getTemplateForException($exception);
        } while (null === $template && null !== ($exception = $exception->getPrevious()));

        if (null === $template) {
            return;
        }

        $this->renderTemplate($template, $statusCode);
    }

    /**
     * Maps an exception to a template.
     *
     * @param \Exception $exception The exception object
     *
     * @return string|null The template name or null.
     */
    private function getTemplateForException(\Exception $exception)
    {
        $class = get_class($exception);

        if (isset($this->mapper[$class])) {
            return $this->mapper[$class];
        }

        return null;
    }

    /**
     * Renders a template and returns the response object.
     *
     * @param string $template   The template name
     * @param int    $statusCode The status code
     */
    private function renderTemplate($template, $statusCode)
    {
        if (!$this->prettyErrorScreens) {
            return;
        }

        $view = '@ContaoCore/Error/' . $template . '.html.twig';
        $parameters = $this->getTemplateParameters($view, $statusCode, $this->event->getRequest()->getBasePath());

        if (null === $parameters) {
            $this->event->setResponse($this->getErrorTemplate());
        } else {
            try {
                $this->event->setResponse(new Response($this->twig->render($view, $parameters), $statusCode));
            } catch (\Twig_Error $e) {
                $this->event->setResponse($this->getErrorTemplate());
            }
        }
    }

    /**
     * Renders the error template and returns the response object.
     *
     * @return Response The response object
     */
    private function getErrorTemplate()
    {
        $parameters = [
            'statusCode' => 500,
            'error' => [
                'error' => 'An error occurred',
                'matter' => 'What\'s the matter?',
                'errorOccurred' => 'An error occurred while executing this script. Something does not work properly. '
                    . 'Additionally an error occurred while trying to display the error message.',
                'howToFix' => 'How can I fix the issue?',
                'errorFixOne' => 'Open the <code>app/logs/error.log</code> file and find the associated error '
                    . 'message (usually the last one).',
                'more' => 'Tell me more, please',
                'errorExplain' => 'The script execution stopped, because something does not work properly. The '
                    . 'actual error message is hidden by this notice for security reasons and can be '
                    . 'found in the <code>app/logs/error.log</code> file (see above). If you do not '
                    . 'understand the error message or do not know how to fix the problem, search the '
                    . '<a href="https://contao.org/faq.html">Contao FAQs</a> or visit the '
                    . '<a href="https://contao.org/support.html">Contao support page</a>.',
            ],
            'template' => '@ContaoCore/Error/error.html.twig',
            'base' => '',
            'adminEmail' => '',
        ];

        return new Response($this->twig->render('@ContaoCore/Error/error.html.twig', $parameters), 500);
    }

    /**
     * Returns the template parameters.
     *
     * @param string $view       The name of the view
     * @param int    $statusCode The HTTP status code
     * @param string $basePath   The base path
     *
     * @return array|null The template parameters or null
     */
    private function getTemplateParameters($view, $statusCode, $basePath)
    {
        if (null === ($labels = $this->loadLanguageStrings())) {
            return null;
        }

        $encoded = StringUtil::encodeEmail($this->config->get('adminEmail'));

        return [
            'statusCode' => $statusCode,
            'error' => $labels,
            'template' => $view,
            'base' => $basePath,
            'adminEmail' => '&#109;&#97;&#105;&#108;&#116;&#111;&#58;' . $encoded,
        ];
    }

    /**
     * Loads the language strings.
     *
     * @return array|null The language strings or null
     */
    private function loadLanguageStrings()
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
}
