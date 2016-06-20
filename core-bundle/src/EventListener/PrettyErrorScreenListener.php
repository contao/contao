<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\PageError404;
use Contao\StringUtil;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var ContaoFrameworkInterface
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
    private $mapper = [
        'Contao\CoreBundle\Exception\ForwardPageNotFoundException' => 'forward_page_not_found',
        'Contao\CoreBundle\Exception\IncompleteInstallationException' => 'incomplete_installation',
        'Contao\CoreBundle\Exception\InsecureInstallationException' => 'insecure_installation',
        'Contao\CoreBundle\Exception\InvalidRequestTokenException' => 'invalid_request_token',
        'Contao\CoreBundle\Exception\NoActivePageFoundException' => 'no_active_page_found',
        'Contao\CoreBundle\Exception\NoLayoutSpecifiedException' => 'no_layout_specified',
        'Contao\CoreBundle\Exception\NoRootPageFoundException' => 'no_root_page_found',
    ];

    /**
     * Constructor.
     *
     * @param bool                     $prettyErrorScreens True to render the error screens
     * @param \Twig_Environment        $twig               The twig environment
     * @param ContaoFrameworkInterface $framework          The Contao framework
     * @param TokenStorageInterface    $tokenStorage       The token storage object
     * @param LoggerInterface|null     $logger             An optional logger service
     */
    public function __construct(
        $prettyErrorScreens,
        \Twig_Environment $twig,
        ContaoFrameworkInterface $framework,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger = null
    ) {
        $this->prettyErrorScreens = $prettyErrorScreens;
        $this->twig = $twig;
        $this->framework = $framework;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
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

        $this->handleException($event);
    }

    /**
     * Handles the exception.
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    private function handleException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        switch (true) {
            case $this->isBackendUser():
                $this->renderBackendException($event);
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

    /**
     * Renders a back end exception.
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    private function renderBackendException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        $this->logException($exception);
        $this->renderTemplate('backend', $this->getStatusCodeForException($exception), $event);
    }

    /**
     * Renders the error screen.
     *
     * @param int                          $type  The error type
     * @param GetResponseForExceptionEvent $event The event object
     */
    private function renderErrorScreenByType($type, GetResponseForExceptionEvent $event)
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

    /**
     * Returns the response of a Contao page handler.
     *
     * @param string $type The error type
     *
     * @return Response|null The response object or null
     */
    private function getResponseFromPageHandler($type)
    {
        $type = 'error_'.$type;

        if (!isset($GLOBALS['TL_PTY'][$type]) || !class_exists($GLOBALS['TL_PTY'][$type])) {
            return null;
        }

        /** @var PageError404 $pageHandler */
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
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    private function renderErrorScreenByException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $statusCode = $this->getStatusCodeForException($exception);

        $this->logException($exception);

        // Look for a template
        do {
            $template = $this->getTemplateForException($exception);
        } while (null === $template && null !== ($exception = $exception->getPrevious()));

        $this->renderTemplate($template ?: 'error', $statusCode, $event);
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
        foreach ($this->mapper as $class => $template) {
            if ($exception instanceof $class) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Renders a template and returns the response object.
     *
     * @param string                       $template   The template name
     * @param int                          $statusCode The status code
     * @param GetResponseForExceptionEvent $event      The event object
     */
    private function renderTemplate($template, $statusCode, GetResponseForExceptionEvent $event)
    {
        if (!$this->prettyErrorScreens) {
            return;
        }

        $view = '@ContaoCore/Error/'.$template.'.html.twig';
        $parameters = $this->getTemplateParameters($view, $statusCode, $event);

        if (null === $parameters) {
            $event->setResponse($this->getErrorTemplate());
        } else {
            try {
                $event->setResponse(new Response($this->twig->render($view, $parameters), $statusCode));
            } catch (\Twig_Error $e) {
                $event->setResponse($this->getErrorTemplate());
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
            'statusName' => 'Internal Server Error',
            'error' => [
                'error' => 'An error occurred',
                'matter' => 'What\'s the matter?',
                'errorOccurred' => 'An error occurred while executing this script. Something does not work properly. '
                    .'Additionally an error occurred while trying to display the error message.',
                'howToFix' => 'How can I fix the issue?',
                'errorFixOne' => 'Search the <code>app/logs</code> folder for the current log file and find the '
                    .'associated error message (usually the last one).',
                'more' => 'Tell me more, please',
                'errorExplain' => 'The script execution stopped, because something does not work properly. The '
                    .'actual error message is hidden by this notice for security reasons and can be '
                    .'found in the current log file (see above). If you do not understand the error message or do '
                    .'not know how to fix the problem, search the '
                    .'<a href="https://contao.org/faq.html">Contao FAQs</a> or visit the '
                    .'<a href="https://contao.org/support.html">Contao support page</a>.',
                'hint' => 'To customize this notice, create a custom Twig template overriding %s.',
            ],
            'template' => '@ContaoCore/Error/error.html.twig',
            'base' => '',
            'adminEmail' => '',
            'exception' => '',
        ];

        return new Response($this->twig->render('@ContaoCore/Error/error.html.twig', $parameters), 500);
    }

    /**
     * Returns the template parameters.
     *
     * @param string                       $view       The name of the view
     * @param int                          $statusCode The HTTP status code
     * @param GetResponseForExceptionEvent $event      The event object
     *
     * @return array|null The template parameters or null
     */
    private function getTemplateParameters($view, $statusCode, GetResponseForExceptionEvent $event)
    {
        if (null === ($labels = $this->loadLanguageStrings())) {
            return null;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter('Contao\Config');

        $encoded = StringUtil::encodeEmail($config->get('adminEmail'));

        return [
            'statusCode' => $statusCode,
            'statusName' => Response::$statusTexts[$statusCode],
            'error' => $labels,
            'template' => $view,
            'base' => $event->getRequest()->getBasePath(),
            'adminEmail' => '&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$encoded,
            'exception' => $event->getException()->getMessage(),
        ];
    }

    /**
     * Loads the language strings.
     *
     * @return array|null The language strings or null
     */
    private function loadLanguageStrings()
    {
        if (!$this->framework->isInitialized()) {
            return null;
        }

        System::loadLanguageFile('exception');

        if (!isset($GLOBALS['TL_LANG']['XPT'])) {
            return null;
        }

        return $GLOBALS['TL_LANG']['XPT'];
    }

    /**
     * Logs the exception.
     *
     * @param \Exception $exception The exception
     */
    private function logException(\Exception $exception)
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->critical('An exception occurred.', ['exception' => $exception]);
    }

    /**
     * Checks if the user is a back end user.
     *
     * @return bool True if the user is a back end user.
     */
    private function isBackendUser()
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

    /**
     * Returns the status code for an exception.
     *
     * @param \Exception $exception The exception
     *
     * @return int The status code
     */
    private function getStatusCodeForException(\Exception $exception)
    {
        return $exception instanceof HttpException ? $exception->getStatusCode() : 500;
    }
}
