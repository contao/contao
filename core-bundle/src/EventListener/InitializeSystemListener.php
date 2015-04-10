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
use Contao\Config;
use Contao\CoreBundle\Session\Attribute\AttributeBagAdapter;
use Contao\Environment;
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
     * @var string
     */
    private $csrfTokenName;

    /**
     * @var bool
     */
    private static $booted = false;

    /**
     * Constructor.
     *
     * @param RouterInterface           $router        The router service
     * @param SessionInterface          $session       The session service
     * @param string                    $rootDir       The kernel root directory
     * @param CsrfTokenManagerInterface $tokenManager  The token manager service
     * @param string                    $csrfTokenName The name of the token
     */
    public function __construct(
        RouterInterface $router,
        SessionInterface $session,
        $rootDir,
        CsrfTokenManagerInterface $tokenManager,
        $csrfTokenName
    ) {
        $this->router        = $router;
        $this->session       = $session;
        $this->rootDir       = dirname($rootDir);
        $this->tokenManager  = $tokenManager;
        $this->csrfTokenName = $csrfTokenName;
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

        $this->setConstants($this->getModeFromContainerScope(), substr($route, strlen($request->getBasePath()) + 1));
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
     * @param string $mode  The mode (BE or FE)
     * @param string $route The route
     *
     * @internal
     */
    protected function setConstants($mode, $route)
    {
        // The constants are deprecated and will be removed in version 5.0.
        define('TL_MODE', $mode);
        define('TL_START', microtime(true));
        define('TL_ROOT', $this->rootDir);
        define('TL_REFERER_ID', substr(md5(TL_START), 0, 8));
        define('TL_SCRIPT', $route);

        // Define the login status constants in the back end (see #4099, #5279)
        if ('BE' === TL_MODE) {
            define('BE_USER_LOGGED_IN', false);
            define('FE_USER_LOGGED_IN', false);
        }
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
        $this->includeHelpers();

        // Set the error and exception handler
        set_error_handler('__error');
        set_exception_handler('__exception');

        // Log PHP errors
        $this->iniSet('error_log', $this->rootDir . '/system/logs/error.log');

        $this->includeBasicClasses();

        // Preload the configuration (see #5872)
        Config::preload();

        // Register the class loader
        ClassLoader::scanAndRegister();

        $this->setRelativePath($request ? $request->getBasePath() : '');
        $this->initializeLegacySessionAccess();
        $this->setDefaultLanguage($request);

        // Fully load the configuration
        $objConfig = Config::getInstance();

        $this->validateInstallation($objConfig, $request);

        Input::initialize();

        $this->configureErrorHandling();
        $this->setTimezone();

        // Set the mbstring encoding
        if (USE_MBSTRING && function_exists('mb_regex_encoding')) {
            mb_regex_encoding(Config::get('characterSet'));
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
        if (!class_exists('Config', false)) {
            require_once __DIR__ . '/../../src/Resources/contao/library/Contao/Config.php';
            class_alias('Contao\\Config', 'Config');
        }

        if (!class_exists('ClassLoader', false)) {
            require_once __DIR__ . '/../../src/Resources/contao/library/Contao/ClassLoader.php';
            class_alias('Contao\\ClassLoader', 'ClassLoader');
        }

        if (!class_exists('TemplateLoader', false)) {
            require_once __DIR__ . '/../../src/Resources/contao/library/Contao/TemplateLoader.php';
            class_alias('Contao\\TemplateLoader', 'TemplateLoader');
        }

        if (!class_exists('ModuleLoader', false)) {
            require_once __DIR__ . '/../../src/Resources/contao/library/Contao/ModuleLoader.php';
            class_alias('Contao\\ModuleLoader', 'ModuleLoader');
        }
    }

    /**
     * Defines the relative path to the installation (see #5339).
     *
     * @param string $basePath The URL base path
     */
    private function setRelativePath($basePath)
    {
        define('TL_PATH', $basePath); // backwards compatibility
    }

    /**
     * Sets the default language.
     *
     * @param Request $request
     */
    private function setDefaultLanguage(Request $request = null)
    {
        if (!$this->session->has('TL_LANGUAGE')) {
            $langs = null !== $request ? $request->getLanguages() : [];
            array_push($langs, 'en'); // see #6533

            foreach ($langs as $lang) {
                if (is_dir(__DIR__ . '/../../src/Resources/contao/languages/' . str_replace('-', '_', $lang))) {
                    $this->session->set('TL_LANGUAGE', $lang);
                    break;
                }
            }
        }

        if ($this->session->has('TL_LANGUAGE')) {
            $GLOBALS['TL_LANGUAGE'] = $this->session->get('TL_LANGUAGE');
        }
    }

    /**
     * Validates the installation.
     *
     * @param Config  $config  The config object
     * @param Request $request The current request if available
     */
    private function validateInstallation(Config $config, Request $request = null)
    {
        if (null === $request || 'contao_backend_install' === $request->attributes->get('_route')) {
            return;
        }

        // Show the "insecure document root" message
        // FIXME: add unit tests for this as soon as die_nicely is an exception
        if (!in_array($request->getClientIp(), ['127.0.0.1', 'fe80::1', '::1']) && '/web' === substr($request->getBasePath(), -4)) {
            die_nicely('be_insecure', 'Your installation is not secure. Please set the document root to the <code>/web</code> subfolder.');
        }

        // Show the "incomplete installation" message
        if (!$config->isComplete()) {
            die_nicely('be_incomplete', 'The installation has not been completed. Open the Contao install tool to continue.');
        }
    }

    /**
     * Configures the error handling.
     */
    private function configureErrorHandling()
    {
        // Always show error messages if logged into the install tool (see #5001)
        if (Input::cookie('TL_INSTALL_AUTH') && !empty($_SESSION['TL_INSTALL_AUTH']) && Input::cookie('TL_INSTALL_AUTH') == $_SESSION['TL_INSTALL_AUTH'] && $_SESSION['TL_INSTALL_EXPIRE'] > time()) {
            Config::set('displayErrors', 1);
        }

        $this->iniSet('display_errors', (Config::get('displayErrors') ? 1 : 0));
        error_reporting((Config::get('displayErrors') || Config::get('logErrors')) ? Config::get('errorReporting') : 0);
    }

    /**
     * Sets the time zone.
     */
    private function setTimezone()
    {
        $this->iniSet('date.timezone', Config::get('timeZone'));
        date_default_timezone_set(Config::get('timeZone'));
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
     */
    private function handleRequestToken(Request $request = null)
    {
        // Backwards compatibility
        if (!defined('REQUEST_TOKEN')) {
            define('REQUEST_TOKEN', $this->tokenManager->getToken($this->csrfTokenName)->getValue());
        }

        // Check the request token upon POST requests
        $token = new CsrfToken($this->csrfTokenName, Input::post('REQUEST_TOKEN'));

        // FIXME: This forces all routes handling POST data to pase a REQUEST_TOKEN
        if ($_POST
            && !$this->tokenManager->isTokenValid($token)
            && null !== $request
        ) {
            // Force a JavaScript redirect upon Ajax requests (IE requires absolute link)
            if ($request->isXmlHttpRequest()) {
                header('HTTP/1.1 204 No Content');
                header('X-Ajax-Location: ' . $this->router->generate('contao_backend'));
            } else {
                header('HTTP/1.1 400 Bad Request');
                die_nicely(
                    'be_referer',
                    'Invalid request token. Please <a href="javascript:window.location.href=window.location.href">go back</a> and try again.'
                );
            }

            exit; // FIXME: throw a ResponseException instead
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
