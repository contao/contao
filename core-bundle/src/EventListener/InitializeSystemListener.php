<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Automator;
use Contao\ClassLoader;
use Contao\Config;
use Contao\Environment;
use Contao\Input;
use Contao\RequestToken;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Initializes the Contao framework.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InitializeSystemListener
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param RouterInterface $router  The router object
     * @param string          $rootDir The kernel root directory
     */
    public function __construct(RouterInterface $router, $rootDir)
    {
        $this->router  = $router;
        $this->rootDir = dirname($rootDir);
    }

    /**
     * Initializes the system upon kernel.request.
     *
     * @param GetResponseEvent $event The event object
     *
     * @throws RouteNotFoundException If the request does not have a route
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->attributes->has('_route')) {
            throw new RouteNotFoundException('Cannot initialize the Contao framework without a route');
        }

        $routeName = $request->attributes->get('_route');

        $this->setConstants(
            $this->getScopeFromRequest($request),
            $this->router->generate($routeName, $request->attributes->get('_route_params'))
        );

        $this->boot($routeName, $request->getBasePath());
    }

    /**
     * Initializes the system upon console.command.
     */
    public function onConsoleCommand()
    {
        $this->setConstants('FE', 'console');

        $this->boot(null, null);
    }

    /**
     * Returns the request scope.
     *
     * @param Request $request The request object
     *
     * @return string The request scope
     */
    private function getScopeFromRequest(Request $request)
    {
        if ($request->attributes->has('_scope') && 'backend' === $request->attributes->get('_scope')) {
            return 'BE';
        }

        return 'FE';
    }

    /**
     * Sets the Contao constants.
     *
     * @param string $scope The scope (BE or FE)
     * @param string $route The route
     */
    private function setConstants($scope, $route)
    {
        // The constants are deprecated and will be removed in version 5.0.
        define('TL_MODE', $scope);
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
     * @param string $routeName The route name
     * @param string $basePath  The URL base path
     */
    private function boot($routeName, $basePath)
    {
        $this->includeHelpers();

        // Try to disable the PHPSESSID
        $this->iniSet('session.use_trans_sid', 0);
        $this->iniSet('session.cookie_httponly', true);

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

        $this->setRelativePath($basePath);
        $this->startSession();
        $this->setDefaultLanguage();

        // Fully load the configuration
        $objConfig = Config::getInstance();

        $this->generateSymlinks($objConfig);
        $this->validateInstallation($objConfig, $routeName);

        Input::initialize();

        $this->configureErrorHandling();
        $this->setTimezone();

        // Set the mbstring encoding
        if (USE_MBSTRING && function_exists('mb_regex_encoding')) {
            mb_regex_encoding(Config::get('characterSet'));
        }

        $this->triggerInitializeSystemHook();
        $this->checkRequestToken();
    }

    /**
     * Includes the helper files.
     */
    private function includeHelpers()
    {
        require __DIR__ . '/../../contao/helper/functions.php';
        require __DIR__ . '/../../contao/config/constants.php';
        require __DIR__ . '/../../contao/helper/interface.php';
        require __DIR__ . '/../../contao/helper/exception.php';
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
            require_once __DIR__ . '/../../contao/library/Contao/Config.php';
            class_alias('Contao\\Config', 'Config');
        }

        if (!class_exists('ClassLoader', false)) {
            require_once __DIR__ . '/../../contao/library/Contao/ClassLoader.php';
            class_alias('Contao\\ClassLoader', 'ClassLoader');
        }

        if (!class_exists('TemplateLoader', false)) {
            require_once __DIR__ . '/../../contao/library/Contao/TemplateLoader.php';
            class_alias('Contao\\TemplateLoader', 'TemplateLoader');
        }

        if (!class_exists('ModuleLoader', false)) {
            require_once __DIR__ . '/../../contao/library/Contao/ModuleLoader.php';
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
        Environment::set('path', $basePath);

        define('TL_PATH', Environment::get('path')); // backwards compatibility
    }

    /**
     * Starts the session.
     */
    private function startSession()
    {
        session_set_cookie_params(0, (Environment::get('path') ?: '/')); // see #5339
        session_start();
    }

    /**
     * Sets the default language.
     */
    private function setDefaultLanguage()
    {
        if (!isset($_SESSION['TL_LANGUAGE'])) {
            $langs = Environment::get('httpAcceptLanguage');
            array_push($langs, 'en'); // see #6533

            foreach ($langs as $lang) {
                if (is_dir(__DIR__ . '/../../contao/languages/' . str_replace('-', '_', $lang))) {
                    $_SESSION['TL_LANGUAGE'] = $lang;
                    break;
                }
            }
        }

        $GLOBALS['TL_LANGUAGE'] = $_SESSION['TL_LANGUAGE'];
    }

    /**
     * Generates the symlinks if the configuration has not been completed.
     *
     * @param Config $config The config object
     */
    private function generateSymlinks(Config $config)
    {
        if (!$config->isComplete() && !is_link($this->rootDir . '/system/themes/flexible')) {
            $automator = new Automator();
            $automator->generateSymlinks();
        }
    }

    /**
     * Validates the installation.
     *
     * @param Config $config    The config object
     * @param string $routeName The route name
     */
    private function validateInstallation(Config $config, $routeName)
    {
        if ('cli' === PHP_SAPI || 'contao_backend_install' === $routeName) {
            return;
        }

        // Show the "insecure document root" message
        if ('/web' === substr(Environment::get('path'), -4)) {
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
     * Checks the request token.
     */
    private function checkRequestToken()
    {
        RequestToken::initialize();

        // Check the request token upon POST requests
        if ($_POST && !RequestToken::validate(Input::post('REQUEST_TOKEN'))) {

            // Force a JavaScript redirect upon Ajax requests (IE requires absolute link)
            if (Environment::get('isAjaxRequest')) {
                header('HTTP/1.1 204 No Content');
                header('X-Ajax-Location: ' . Environment::get('base') . 'contao/');
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
}
