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
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\String;
use Contao\System;
use Symfony\Bundle\TwigBundle\TwigEngine;
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
 */
class PrettyErrorScreenListener
{
    /**
     * @var bool
     */
    private $prettyErrorScreens;

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
    private $mapper = [
        'Contao\\CoreBundle\\Exception\\AccessDeniedException'           => 'access_denied',
        'Contao\\CoreBundle\\Exception\\ForwardPageNotFoundException'    => 'forward_page_not_found',
        'Contao\\CoreBundle\\Exception\\IncompleteInstallationException' => 'incomplete_installation',
        'Contao\\CoreBundle\\Exception\\InsecureInstallationException'   => 'insecure_installation',
        'Contao\\CoreBundle\\Exception\\InvalidRequestTokenException'    => 'invalid_request_token',
        'Contao\\CoreBundle\\Exception\\NoActivePageFoundException'      => 'no_active_page_found',
        'Contao\\CoreBundle\\Exception\\NoLayoutSpecifiedException'      => 'no_layout_specified',
        'Contao\\CoreBundle\\Exception\\NoRootPageFoundException'        => 'no_root_page_found',
        'Contao\\CoreBundle\\Exception\\PageNotFoundException'           => 'page_not_found',
        'Contao\\CoreBundle\\Exception\\ServiceUnavailableException'     => 'service_unavailable',
    ];

    /**
     * Constructor.
     *
     * @param bool          $prettyErrorScreens True to render the error screens
     * @param TwigEngine    $twig               The twig rendering engine
     * @param ConfigAdapter $config             The config adapter
     */
    public function __construct($prettyErrorScreens, TwigEngine $twig, ConfigAdapter $config)
    {
        $this->prettyErrorScreens = $prettyErrorScreens;
        $this->twig               = $twig;
        $this->config             = $config;
    }

    /**
     * Map the exception to an error screen.
     *
     * @param GetResponseForExceptionEvent $event The event object
     *
     * @todo Log the exception?
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        switch (true) {
            case $exception instanceof AccessDeniedHttpException:
                $this->renderErrorScreen(403, $event);
                break;

            case $exception instanceof BadRequestHttpException:
            case $exception instanceof InternalServerErrorHttpException:
                $this->checkExceptionChain($event);
                break;

            case $exception instanceof NotFoundHttpException:
                $this->renderErrorScreen(404, $event);
                break;

            case $exception instanceof ServiceUnavailableHttpException:
                $this->renderMaintenanceScreen($event);
                break;
        }
    }

    /**
     * Renders the error screen.
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    private function renderErrorScreen($type, GetResponseForExceptionEvent $event)
    {
        static $processing;

        if (true === $processing) {
            return;
        }

        $processing = true;

        if (null !== ($response = $this->renderPageHandler($type, $event))) {
            $event->setResponse($response);
        } else {
            $this->checkExceptionChain($event);
        }

        $processing  = false;
    }

    /**
     * Renders a Contao page handler.
     *
     * @param string $type The error type
     *
     * @return Response|null The response object or null
     */
    private function renderPageHandler($type)
    {
        $type = "error_$type";

        if (!isset($GLOBALS['TL_PTY'][$type]) || !class_exists($GLOBALS['TL_PTY'][$type])) {
            return null;
        }

        /** @var \PageError403 $pageHandler */
        $pageHandler = new $GLOBALS['TL_PTY'][$type]();

        try {
            return $pageHandler->getResponse(false); // FIXME: the class requires a numeric $pageId
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Renders the maintenance screen.
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    private function renderMaintenanceScreen(GetResponseForExceptionEvent $event)
    {
        $event->setResponse($this->renderTemplate('service_unavailable', 503));
    }

    /**
     * Checks the exception chain for a known exception.
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    private function checkExceptionChain(GetResponseForExceptionEvent $event)
    {
        $template   = null;
        $exception  = $event->getException();
        $statusCode = 500;

        // Check the status code
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
        }

        // Look for a template
        do {
            if (null !== ($template = $this->getTemplateForException($exception))) {
                break;
            }
        } while (null !== ($exception = $exception->getPrevious()));

        if (null !== $template) {
            $event->setResponse($this->renderTemplate($template, $statusCode, $event->getRequest()->getBasePath()));
        }
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
     * @param string $basePath   The base path
     *
     * @return Response The response object
     */
    private function renderTemplate($template, $statusCode, $basePath = '')
    {
        $view = "@ContaoCore/Error/$template.html.twig";

        if (!$this->twig->exists($view)) {
            return $this->renderErrorTemplate();
        }

        $parameters = $this->getTemplateParameters($view, $statusCode, $basePath);

        if (null === $parameters) {
            return $this->renderErrorTemplate();
        }

        return $this->twig->renderResponse($view, $parameters)->setStatusCode($statusCode);
    }

    /**
     * Renders the error template and returns the response object.
     *
     * @return Response The response object
     */
    private function renderErrorTemplate()
    {
        $parameters =  [
            'statusCode' => 500,
            'error'      => [
                'error'         => 'An error occurred',
                'matter'        => 'What\'s the matter?',
                'errorOccurred' => 'An error occurred while executing this script. Something does not work properly. Additionally an error occurred while trying to display the error message.',
                'howToFix'      => 'How can I fix the issue?',
                'errorFixOne'   => 'Open the <code>app/logs/error.log</code> file and find the associated error message (usually the last one).',
                'more'          => 'Tell me more, please',
                'errorExplain'  => 'The script execution stopped, because something does not work properly. The actual error message is hidden by this notice for security reasons and can be found in in the <code>app/logs/error.log</code> file (see above). If you do not understand the error message or do not know how to fix the problem, search the <a href="https://contao.org/faq.html" target="_blank">Contao FAQs</a> or visit the <a href="https://contao.org/support.html" target="_blank">Contao support page</a>.',
            ],
            'template'   => '@ContaoCore/Error/error.html.twig',
            'base'       => '',
            'adminEmail' => '',
        ];

        return $this->twig->renderResponse('@ContaoCore/Error/error.html.twig', $parameters)->setStatusCode(500);
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

        $encoded = String::encodeEmail($this->config->get('adminEmail'));

        return [
            'statusCode' => $statusCode,
            'error'      => $labels,
            'template'   => $view,
            'base'       => $basePath,
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
