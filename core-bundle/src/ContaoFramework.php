<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle;

use Contao\ClassLoader;
use Contao\CoreBundle\Adapter\ConfigAdapter;
use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\Input;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Initializes the Contao framework.
 *
 * @author Dominik Tomasi <https://github.com/dtomasi>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoFramework
{

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var ConfigAdapter
     */
    private $config;

    /**
     * @var string
     */
    private $csrfTokenName;

    /**
     * @var int
     */
    private $errorLevel;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var array
     */
    private $basicClasses = [
        'Config',
        'ClassLoader',
        'TemplateLoader',
        'ModuleLoader',
    ];

    /**
     * Constructor.
     *
     * @param RequestStack              $requestStack  The RequestStack
     * @param RouterInterface           $router        The router service
     * @param SessionInterface          $session       The session service
     * @param string                    $rootDir       The kernel root directory
     * @param CsrfTokenManagerInterface $tokenManager  The token manager service
     * @param string                    $csrfTokenName The name of the token
     * @param ConfigAdapter             $config        The config adapter object
     * @param int                       $errorLevel    The PHP error level
     */
    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        SessionInterface $session,
        $rootDir,
        CsrfTokenManagerInterface $tokenManager,
        $csrfTokenName,
        ConfigAdapter $config,
        $errorLevel
    ) {
        $this->requestStack  = $requestStack;
        $this->router        = $router;
        $this->session       = $session;
        $this->rootDir       = dirname($rootDir);
        $this->tokenManager  = $tokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->config        = $config;
        $this->errorLevel    = $errorLevel;
    }

    /**
     * Check Framework is initialized
     *
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Start Initialization-Process
     */
    public function initialize()
    {
        if ($this->isInitialized()) {
            return;
        }

        $this->initialized = true;

        $request = $this->requestStack->getCurrentRequest();

        $this->setConstants($request);

        // Set the error_reporting level
        error_reporting($this->errorLevel);

        $this->includeHelpers();

        // TODO: use Monolog to log errors
        $this->iniSet('error_log', $this->rootDir . '/system/logs/error.log');

        $this->includeBasicClasses();

        // Preload the configuration (see #5872)
        $this->config->preload();

        // Register the class loader
        ClassLoader::scanAndRegister();

        $this->initializeLegacySessionAccess();
        $this->setDefaultLanguage($request);

        // Fully load the configuration
        $this->config->initialize();

        $this->validateInstallation($request);

        Input::initialize();

        $this->setTimezone();

        // Set the mbstring encoding
        if (USE_MBSTRING && function_exists('mb_regex_encoding')) {
            mb_regex_encoding($this->config->get('characterSet'));
        }

        $this->triggerInitializeSystemHook();
        $this->handleRequestToken($request);
    }

    /**
     * Defines Constants required for Contao-Framework
     *
     * @param Request $request
     */
    private function setConstants(Request $request = null)
    {
        $scope = $this->requestStack->getCurrentRequest()->attributes->get('_scope');

        if ($scope === (ContaoCoreBundle::SCOPE_BACKEND)) {
            define('TL_MODE', 'BE');
        } else {
            define('TL_MODE', 'FE');
        }

        define('TL_START', microtime(true));
        define('TL_ROOT', $this->rootDir);

        if (null !== $request) {
            define('TL_REFERER_ID', $request->attributes->get('_contao_referer_id', ''));
        }

        if (null !== $request) {
            $route = $this->router->generate(
                $request->attributes->get('_route'),
                $request->attributes->get('_route_params')
            );

            $route = substr($route, strlen($request->getBasePath()) + 1);

        } else {
            $route = 'console';
        }

        define('TL_SCRIPT', $route);

        if ($scope === ContaoCoreBundle::SCOPE_BACKEND) {
            define('BE_USER_LOGGED_IN', false);
            define('FE_USER_LOGGED_IN', false);
        }

        if (null !== $request) {
            define('TL_PATH', $request->getBasePath());
        }
    }

    /**
     * Includes some helper files
     */
    private function includeHelpers()
    {
        require __DIR__ . '/Resources/contao/helper/functions.php';
        require __DIR__ . '/Resources/contao/config/constants.php';
        require __DIR__ . '/Resources/contao/helper/interface.php';
        require __DIR__ . '/Resources/contao/helper/exception.php';
    }

    /**
     * Tries to set a php.ini configuration option.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     */
    private function iniSet($key, $value)
    {
        if (function_exists('ini_set')) {
            ini_set($key, $value);
        }
    }

    /**
     * Includes the basic classes required for further processing.
     */
    private function includeBasicClasses()
    {
        foreach ($this->basicClasses as $class) {
            if (!class_exists($class, false)) {
                require_once __DIR__ . '/Resources/contao/library/Contao/' . $class . '.php';
                class_alias('Contao\\' . $class, $class);
            }
        }
    }

    /**
     * Sets the default language.
     *
     * @param Request|null $request
     */
    private function setDefaultLanguage(Request $request = null)
    {
        $language = 'en';

        if (null !== $request) {
            $language = str_replace('_', '-', $request->getLocale());
        }

        // Backwards compatibility
        $GLOBALS['TL_LANGUAGE']  = $language;
        $_SESSION['TL_LANGUAGE'] = $language;
    }

    /**
     * Validates the installation.
     *
     * @param Request|null $request The current request if available
     *
     * @throws IncompleteInstallationException If the installation has not been completed
     */
    private function validateInstallation(Request $request = null)
    {
        if (null === $request || 'contao_backend_install' === $request->attributes->get('_route')) {
            return;
        }

        // Show the "incomplete installation" message
        if (!$this->config->isComplete()) {
            throw new IncompleteInstallationException(
                'The installation has not been completed. Open the Contao install tool to continue.'
            );
        }
    }

    /**
     * Sets the time zone.
     */
    private function setTimezone()
    {
        $this->iniSet('date.timezone', $this->config->get('timeZone'));
        date_default_timezone_set($this->config->get('timeZone'));
    }

    /**
     * Triggers the initializeSystem hook (see #5665).
     */
    private function triggerInitializeSystemHook()
    {
        if (isset($GLOBALS['TL_HOOKS']['initializeSystem']) && is_array($GLOBALS['TL_HOOKS']['initializeSystem'])) {
            foreach ($GLOBALS['TL_HOOKS']['initializeSystem'] as $callback) {
                System::importStatic($callback[0])->$callback[1]();
            }
        }

        if (file_exists($this->rootDir . '/system/config/initconfig.php')) {
            include $this->rootDir . '/system/config/initconfig.php';
        }
    }

    /**
     * Handles the request token.
     *
     * @param Request|null $request
     *
     * @throws AjaxRedirectResponseException|InvalidRequestTokenException If the token is invalid
     */
    private function handleRequestToken(Request $request = null)
    {
        // Backwards compatibility
        if (!defined('REQUEST_TOKEN')) {
            define('REQUEST_TOKEN', $this->tokenManager->getToken($this->csrfTokenName)->getValue());
        }

        if (null === $request || 'POST' !== $request->getRealMethod()) {
            return;
        }

        $token = new CsrfToken($this->csrfTokenName, Input::post('REQUEST_TOKEN'));

        if ($this->tokenManager->isTokenValid($token)) {
            return;
        }

        if ($request->isXmlHttpRequest()) {
            throw new AjaxRedirectResponseException($this->router->generate('contao_backend'));
        }

        throw new InvalidRequestTokenException('Invalid request token. Please reload the page and try again.');
    }

    /**
     * Initializes session access for $_SESSION['FE_DATA'] and $_SESSION['BE_DATA'].
     */
    private function initializeLegacySessionAccess()
    {
        $_SESSION['BE_DATA'] = $this->session->getBag('contao_backend');
        $_SESSION['FE_DATA'] = $this->session->getBag('contao_frontend');
    }
}
