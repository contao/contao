<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\ClassLoader;
use Contao\CoreBundle\Adapter\ConfigAdapter;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Session\Attribute\AttributeBagAdapter;
use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\Input;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Initializes the Contao framework.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InitializeSystemListener extends ScopeAwareListener
{
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
     * @var bool
     */
    private static $booted = false;

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
     * @param RouterInterface           $router        The router service
     * @param SessionInterface          $session       The session service
     * @param string                    $rootDir       The kernel root directory
     * @param CsrfTokenManagerInterface $tokenManager  The token manager service
     * @param string                    $csrfTokenName The name of the token
     * @param ConfigAdapter             $config        The config adapter object
     */
    public function __construct(
        RouterInterface $router,
        SessionInterface $session,
        $rootDir,
        CsrfTokenManagerInterface $tokenManager,
        $csrfTokenName,
        ConfigAdapter $config
    ) {
        $this->router        = $router;
        $this->session       = $session;
        $this->rootDir       = dirname($rootDir);
        $this->tokenManager  = $tokenManager;
        $this->csrfTokenName = $csrfTokenName;
        $this->config        = $config;
    }

    /**
     * Initializes the system upon kernel.request.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (true === self::$booted || (!$this->isFrontendScope() && !$this->isBackendScope())) {
            return;
        }

        // Set before calling any methods to prevent recursive booting
        self::$booted = true;

        $request = $event->getRequest();

        $route = $this->router->generate(
            $request->attributes->get('_route'),
            $request->attributes->get('_route_params')
        );

        $basePath = $request->getBasePath();

        $this->setConstants(
            $this->getModeFromContainerScope(),
            substr($route, strlen($basePath) + 1),
            $basePath,
            $request->attributes->get('_contao_referer_id')
        );

        $this->boot($request);
    }

    /**
     * Initializes the system upon console.command.
     */
    public function onConsoleCommand()
    {
        if (true === self::$booted) {
            return;
        }

        // Set before calling any methods to prevent recursive booting
        self::$booted = true;

        $this->setConstants('FE', 'console');
        $this->boot();
    }

    /**
     * Sets the Contao constants.
     *
     * @param string $mode      The mode (BE or FE)
     * @param string $route     The route
     * @param string $basePath  The base path
     * @param string $refererId The referer ID
     *
     * @internal
     */
    protected function setConstants($mode, $route, $basePath = '', $refererId = '')
    {
        // The constants are deprecated and will be removed in version 5.0.
        define('TL_MODE', $mode);
        define('TL_START', microtime(true));
        define('TL_ROOT', $this->rootDir);
        define('TL_REFERER_ID', $refererId);
        define('TL_SCRIPT', $route);

        // Define the login status constants in the back end (see #4099, #5279)
        if ('BE' === TL_MODE) {
            define('BE_USER_LOGGED_IN', false);
            define('FE_USER_LOGGED_IN', false);
        }

        // Define the relative path to the installation (see #5339)
        define('TL_PATH', $basePath);
    }

    /**
     * Boots the Contao framework.
     *
     * @param Request $request The request object
     *
     * @internal
     */
    protected function boot(Request $request = null)
    {
        // Set the error_reporting level
        error_reporting($this->container->getParameter('contao.error_level'));

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
     * Returns the TL_MODE value for the container scope.
     *
     * @return string The TL_MODE value
     */
    private function getModeFromContainerScope()
    {
        if ($this->isBackendScope()) {
            return 'BE';
        }

        if ($this->isFrontendScope()) {
            return 'FE';
        }

        return null;
    }

    /**
     * Includes the helper files.
     */
    private function includeHelpers()
    {
        require __DIR__ . '/../../src/Resources/contao/helper/functions.php';
        require __DIR__ . '/../../src/Resources/contao/config/constants.php';
        require __DIR__ . '/../../src/Resources/contao/helper/interface.php';
        require __DIR__ . '/../../src/Resources/contao/helper/exception.php';
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
                require_once __DIR__ . "/../../src/Resources/contao/library/Contao/$class.php";
                class_alias("Contao\\$class", $class);
            }
        }
    }

    /**
     * Sets the default language.
     *
     * @param Request $request
     */
    private function setDefaultLanguage(Request $request = null)
    {
        if (!$this->session->has('TL_LANGUAGE')) {
            $langs = $request ? $request->getLanguages() : [];
            array_push($langs, 'en'); // see #6533

            foreach ($langs as $lang) {
                if (is_dir(__DIR__ . '/../../src/Resources/contao/languages/' . str_replace('-', '_', $lang))) {
                    $this->session->set('TL_LANGUAGE', $lang);
                    break;
                }
            }
        }

        $GLOBALS['TL_LANGUAGE']  = $this->session->get('TL_LANGUAGE');
        $_SESSION['TL_LANGUAGE'] = $this->session->get('TL_LANGUAGE'); // backwards compatibility
    }

    /**
     * Validates the installation.
     *
     * @param Request $request The current request if available
     *
     * @throws InsecureInstallationException   If the document root is not set correctly
     * @throws IncompleteInstallationException If the installation has not been completed
     */
    private function validateInstallation(Request $request = null)
    {
        if (null === $request || 'contao_backend_install' === $request->attributes->get('_route')) {
            return;
        }

        // Show the "insecure document root" message
        if (!in_array($request->getClientIp(), ['127.0.0.1', 'fe80::1', '::1']) && '/web' === substr($request->getBasePath(), -4)) {
            throw new InsecureInstallationException('Your installation is not secure. Please set the document root to the <code>/web</code> subfolder.');
        }

        // Show the "incomplete installation" message
        if (!$this->config->isComplete()) {
            throw new IncompleteInstallationException('The installation has not been completed. Open the Contao install tool to continue.');
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
     * @param Request $request
     *
     * @throws AjaxRedirectResponseException|InvalidRequestTokenException If the token is invalid
     */
    private function handleRequestToken(Request $request = null)
    {
        // Backwards compatibility
        if (!defined('REQUEST_TOKEN')) {
            define('REQUEST_TOKEN', $this->tokenManager->getToken($this->csrfTokenName)->getValue());
        }

        // Check the request token upon POST requests
        $token = new CsrfToken($this->csrfTokenName, Input::post('REQUEST_TOKEN'));

        // FIXME: This forces all routes handling POST data to pass a REQUEST_TOKEN
        if ($_POST && null !== $request && !$this->tokenManager->isTokenValid($token)) {
            if ($request->isXmlHttpRequest()) {
                throw new AjaxRedirectResponseException($this->router->generate('contao_backend'));
            }

            throw new InvalidRequestTokenException(
                'Invalid request token. Please <a href="javascript:window.location.href=window.location.href">reload the page</a> and try again.'
            );
        }
    }

    /**
     * Initializes session access for $_SESSION['FE_DATA'] and $_SESSION['BE_DATA'].
     */
    private function initializeLegacySessionAccess()
    {
        /** @var AttributeBagInterface $feBag */
        $feBag = $this->session->getBag('contao_frontend');

        /** @var AttributeBagInterface $beBag */
        $beBag = $this->session->getBag('contao_backend');

        $_SESSION['FE_DATA'] = new AttributeBagAdapter($feBag);
        $_SESSION['BE_DATA'] = new AttributeBagAdapter($beBag);
    }
}
