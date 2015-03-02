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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Initializes the Contao framework.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
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
        $this->rootDir = $rootDir;
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
        $request = $event->getRequest();

        if (!$request->attributes->has('_route')) {
            throw new RouteNotFoundException('Cannot initialize the Contao framework without a route');
        }

        $routeName = $request->attributes->get('_route');

        if ($request->attributes->has('_scope') && 'backend' === $request->attributes->get('_scope')) {
            $mode = 'BE';
        } else {
            $mode = 'FE';
        }

        $this->initialize($mode, $this->router->generate($routeName, $request->attributes->get('_route_params')));
    }

    /**
     * Initializes the system upon console.command.
     */
    public function onConsoleCommand()
    {
        $this->initialize('FE', 'console');
    }

    /**
     * Bootstraps the Contao framework.
     *
     * @param string $mode  The scope (BE or FE)
     * @param string $route The route
     */
    private function initialize($mode, $route)
    {
        // We define these constants here for reasons of backwards compatibility only.
        // They will be removed in Contao 5 and should not be used anymore.
        define('TL_MODE', $mode);
        define('TL_START', microtime(true));
        define('TL_ROOT', dirname($this->rootDir));
        define('TL_REFERER_ID', substr(md5(TL_START), 0, 8));
        define('TL_SCRIPT', $route);

        // Define the login status constants in the back end (see #4099, #5279)
        if ('BE' === TL_MODE) {
            define('BE_USER_LOGGED_IN', false);
            define('FE_USER_LOGGED_IN', false);
        }

        require __DIR__ . '/../../contao/helper/functions.php';
        require __DIR__ . '/../../contao/config/constants.php';
        require __DIR__ . '/../../contao/helper/interface.php';
        require __DIR__ . '/../../contao/helper/exception.php';

        // Try to disable the PHPSESSID
        @ini_set('session.use_trans_sid', 0);
        @ini_set('session.cookie_httponly', true);

        // Set the error and exception handler
        @set_error_handler('__error');
        @set_exception_handler('__exception');

        // Log PHP errors
        @ini_set('error_log', TL_ROOT . '/system/logs/error.log');

        // Include the Config class if it does not yet exist
        if (!class_exists('Config', false)) {
            require_once __DIR__ . '/../../contao/library/Contao/Config.php';
            class_alias('Contao\\Config', 'Config');
        }

        // Include some classes required for further processing
        require_once __DIR__ . '/../../contao/library/Contao/ClassLoader.php';
        class_alias('Contao\\ClassLoader', 'ClassLoader');

        require_once __DIR__ . '/../../contao/library/Contao/TemplateLoader.php';
        class_alias('Contao\\TemplateLoader', 'TemplateLoader');

        require_once __DIR__ . '/../../contao/library/Contao/ModuleLoader.php';
        class_alias('Contao\\ModuleLoader', 'ModuleLoader');

        // Preload the configuration (see #5872)
        Config::preload();

        // Try to load the modules
        try {
            ClassLoader::scanAndRegister();
        } catch (\UnresolvableDependenciesException $e) {
            die($e->getMessage()); // see #6343
        }

        // Override the SwiftMailer defaults
        \Swift::init(function() {
            $preferences = \Swift_Preferences::getInstance();
            $preferences->setTempDir(TL_ROOT . '/system/tmp')->setCacheType('disk');
            $preferences->setCharset(Config::get('characterSet'));
        });

        // Define the relative path to the installation (see #5339)
        if (Config::has('websitePath') && TL_SCRIPT /* FIXME */ != 'contao/install.php') {
            Environment::set('path', Config::get('websitePath'));
        } elseif ('BE' === TL_MODE) {
            Environment::set('path', preg_replace('/\/contao\/[a-z]+\.php$/i', '', Environment::get('scriptName')));
        }

        define('TL_PATH', Environment::get('path')); // backwards compatibility

        // Start the session
        @session_set_cookie_params(0, (Environment::get('path') ?: '/')); // see #5339
        @session_start();

        // Set the default language
        if (!isset($_SESSION['TL_LANGUAGE'])) {
            $langs = Environment::get('httpAcceptLanguage');
            array_push($langs, 'en'); // see #6533

            foreach ($langs as $lang) {
                if (is_dir(__DIR__ . '/../../contao/languages/' . str_replace('-', '_', $lang))) {
                    $_SESSION['TL_LANGUAGE'] = $lang;
                    break;
                }
            }

            unset($langs, $lang);
        }

        $GLOBALS['TL_LANGUAGE'] = $_SESSION['TL_LANGUAGE'];

        // Fully load the configuration
        $objConfig = Config::getInstance();

        // Generate the symlinks before any potential output
        if (!$objConfig->isComplete() && !is_link(TL_ROOT . '/system/themes/flexible')) {
            $automator = new Automator();
            $automator->generateSymlinks();
        }

        // Show the "insecure document root" message
        if ('cli' !== PHP_SAPI && 'contao/install.php' !== TL_SCRIPT /* FIXME */ && '/web' !== substr(Environment::get('path'), -4) && !Config::get('ignoreInsecureRoot')) {
            die_nicely('be_insecure', 'Your installation is not secure. Please set the document root to the <code>/web</code> subfolder.');
        }

        // Show the "incomplete installation" message
        if ('cli' !== PHP_SAPI && 'contao/install.php' !== TL_SCRIPT /* FIXME */ && !$objConfig->isComplete()) {
            die_nicely('be_incomplete', 'The installation has not been completed. Open the Contao install tool to continue.');
        }

        Input::initialize();

        // Always show error messages if logged into the install tool (see #5001)
        if (Input::cookie('TL_INSTALL_AUTH') && !empty($_SESSION['TL_INSTALL_AUTH']) && Input::cookie('TL_INSTALL_AUTH') == $_SESSION['TL_INSTALL_AUTH'] && $_SESSION['TL_INSTALL_EXPIRE'] > time()) {
            Config::set('displayErrors', 1);
        }

        // Configure the error handling
        @ini_set('display_errors', (Config::get('displayErrors') ? 1 : 0));
        error_reporting((Config::get('displayErrors') || Config::get('logErrors')) ? Config::get('errorReporting') : 0);

        // Set the timezone
        @ini_set('date.timezone', Config::get('timeZone'));
        @date_default_timezone_set(Config::get('timeZone'));

        // Set the mbstring encoding
        if (USE_MBSTRING && function_exists('mb_regex_encoding')) {
            mb_regex_encoding(Config::get('characterSet'));
        }

        // HOOK: add custom logic (see #5665)
        if (isset($GLOBALS['TL_HOOKS']['initializeSystem']) && is_array($GLOBALS['TL_HOOKS']['initializeSystem'])) {
            foreach ($GLOBALS['TL_HOOKS']['initializeSystem'] as $callback) {
                System::importStatic($callback[0])->$callback[1]();
            }
        }

        // Include the custom initialization file
        if (file_exists(TL_ROOT . '/system/config/initconfig.php')) {
            include TL_ROOT . '/system/config/initconfig.php';
        }

        RequestToken::initialize();

        // Check the request token upon POST requests
        if ($_POST && !RequestToken::validate(Input::post('REQUEST_TOKEN'))) {

            // Force a JavaScript redirect upon Ajax requests (IE requires absolute link)
            if (Environment::get('isAjaxRequest')) {
                header('HTTP/1.1 204 No Content');
                header('X-Ajax-Location: ' . Environment::get('base') . 'contao/');
            } else {
                header('HTTP/1.1 400 Bad Request');
                die_nicely('be_referer', 'Invalid request token. Please <a href="javascript:window.location.href=window.location.href">go back</a> and try again.');
            }

            exit; // FIXME: throw a ResponseException or DieNicelyException instead
        }
    }
}
