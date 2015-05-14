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
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Initializes the Contao framework.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Dominik Tomasi <https://github.com/dtomasi>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoFramework
{
    use ContainerAwareTrait;

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
     * @var Request
     */
    private $request;

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
     * @param RequestStack              $requestStack  The request stack
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
        $this->router        = $router;
        $this->session       = $session;
        $this->rootDir       = dirname($rootDir);
        $this->tokenManager  = $tokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->config        = $config;
        $this->errorLevel    = $errorLevel;
        $this->request       = $requestStack->getCurrentRequest();
    }

    /**
     * Checks if the framework has been initialized.
     *
     * @return bool True if the framework has been initialized
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Initializes the framework.
     */
    public function initialize()
    {
        if ($this->isInitialized()) {
            return;
        }

        // Set before calling any methods to prevent recursion
        $this->initialized = true;

        $this->setConstants();
        $this->initializeFramework();
    }

    /**
     * Sets the Contao constants.
     */
    private function setConstants()
    {
        // The constants are deprecated and will be removed in Contao 5.0.
        define('TL_MODE', $this->getMode());
        define('TL_START', microtime(true));
        define('TL_ROOT', $this->rootDir);
        define('TL_REFERER_ID', $this->getRefererId());
        define('TL_SCRIPT', $this->getRoute());

        // Define the login status constants in the back end (see #4099, #5279)
        if ($this->container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND)) {
            define('BE_USER_LOGGED_IN', false);
            define('FE_USER_LOGGED_IN', false);
        }

        // Define the relative path to the installation (see #5339)
        define('TL_PATH', $this->getPath());
    }

    /**
     * Returns the TL_MODE value.
     *
     * @return string|null The TL_MODE value or null
     */
    private function getMode()
    {
        if ($this->container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND)) {
            return 'BE';
        }

        if ($this->container->isScopeActive(ContaoCoreBundle::SCOPE_FRONTEND)) {
            return 'FE';
        }

        return null;
    }

    /**
     * Returns the referer ID.
     *
     * @return string|null The referer ID or null
     */
    private function getRefererId()
    {
        if (null === $this->request) {
            return null;
        }

        return $this->request->attributes->get('_contao_referer_id', '');
    }

    /**
     * Returns the route.
     *
     * @return string The route
     */
    private function getRoute()
    {
        if (null === $this->request) {
            return 'console';
        }

        $route = $this->router->generate(
            $this->request->attributes->get('_route'),
            $this->request->attributes->get('_route_params')
        );

        return substr($route, strlen($this->request->getBasePath()) + 1);
    }

    /**
     * Returns the base path.
     *
     * @return string|null The base path
     */
    private function getPath()
    {
        if (null === $this->request) {
            return null;
        }

        return $this->request->getBasePath();
    }

    /**
     * Initializes the framework.
     */
    private function initializeFramework()
    {
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
        $this->setDefaultLanguage();

        // Fully load the configuration
        $this->config->initialize();

        $this->validateInstallation();

        Input::initialize();

        $this->setTimezone();

        // Set the mbstring encoding
        if (USE_MBSTRING && function_exists('mb_regex_encoding')) {
            mb_regex_encoding($this->config->get('characterSet'));
        }

        $this->triggerInitializeSystemHook();
        $this->handleRequestToken();
    }

    /**
     * Includes some helper files.
     */
    private function includeHelpers()
    {
        require __DIR__ . '/Resources/contao/helper/functions.php';
        require __DIR__ . '/Resources/contao/config/constants.php';
        require __DIR__ . '/Resources/contao/helper/interface.php';
        require __DIR__ . '/Resources/contao/helper/exception.php';
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
     * Initializes session access for $_SESSION['FE_DATA'] and $_SESSION['BE_DATA'].
     */
    private function initializeLegacySessionAccess()
    {
        $_SESSION['BE_DATA'] = $this->session->getBag('contao_backend');
        $_SESSION['FE_DATA'] = $this->session->getBag('contao_frontend');
    }

    /**
     * Sets the default language.
     */
    private function setDefaultLanguage()
    {
        $language = 'en';

        if (null !== $this->request) {
            $language = str_replace('_', '-', $this->request->getLocale());
        }

        // Backwards compatibility
        $GLOBALS['TL_LANGUAGE']  = $language;
        $_SESSION['TL_LANGUAGE'] = $language;
    }

    /**
     * Validates the installation.
     *
     * @throws IncompleteInstallationException If the installation has not been completed
     */
    private function validateInstallation()
    {
        if (null === $this->request || 'contao_backend_install' === $this->request->attributes->get('_route')) {
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
     * @throws AjaxRedirectResponseException|InvalidRequestTokenException If the token is invalid
     */
    private function handleRequestToken()
    {
        // Backwards compatibility
        if (!defined('REQUEST_TOKEN')) {
            define('REQUEST_TOKEN', $this->tokenManager->getToken($this->csrfTokenName)->getValue());
        }

        if (null === $this->request || 'POST' !== $this->request->getRealMethod()) {
            return;
        }

        $token = new CsrfToken($this->csrfTokenName, $this->request->request->get('REQUEST_TOKEN'));

        if ($this->tokenManager->isTokenValid($token)) {
            return;
        }

        if ($this->request->isXmlHttpRequest()) {
            throw new AjaxRedirectResponseException($this->router->generate('contao_backend'));
        }

        throw new InvalidRequestTokenException('Invalid request token. Please reload the page and try again.');
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
}
