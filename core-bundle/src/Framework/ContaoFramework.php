<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

use Contao\ClassLoader;
use Contao\Config;
use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\Input;
use Contao\RequestToken;
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Initializes the Contao framework.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Dominik Tomasi <https://github.com/dtomasi>
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @internal Do not instantiate this class in your code; use the "contao.framework" service instead
 */
class ContaoFramework implements ContaoFrameworkInterface
{
    use ScopeAwareTrait;

    /**
     * @var bool
     */
    private static $initialized = false;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var int
     */
    private $errorLevel;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var array
     */
    private $adapterCache = [];

    /**
     * @var array
     */
    private $basicClasses = [
        'System',
        'Config',
        'ClassLoader',
        'TemplateLoader',
        'ModuleLoader',
    ];

    /**
     * Constructor.
     *
     * @param RequestStack     $requestStack
     * @param RouterInterface  $router
     * @param SessionInterface $session
     * @param string           $rootDir
     * @param int              $errorLevel
     */
    public function __construct(RequestStack $requestStack, RouterInterface $router, SessionInterface $session, $rootDir, $errorLevel)
    {
        $this->router = $router;
        $this->session = $session;
        $this->rootDir = $rootDir;
        $this->errorLevel = $errorLevel;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function isInitialized()
    {
        return self::$initialized;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    public function initialize()
    {
        if ($this->isInitialized()) {
            return;
        }

        // Set before calling any methods to prevent recursion
        self::$initialized = true;

        if (null === $this->container) {
            throw new \LogicException('The service container has not been set.');
        }

        // Set the current request
        $this->request = $this->requestStack->getCurrentRequest();

        $this->setConstants();
        $this->initializeFramework();
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance($class, $args = [])
    {
        if (in_array('getInstance', get_class_methods($class))) {
            return call_user_func_array([$class, 'getInstance'], $args);
        }

        $reflection = new \ReflectionClass($class);

        return $reflection->newInstanceArgs($args);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdapter($class)
    {
        if (!isset($this->adapterCache[$class])) {
            $this->adapterCache[$class] = new Adapter($class);
        }

        return $this->adapterCache[$class];
    }

    /**
     * Sets the Contao constants.
     *
     * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0
     */
    private function setConstants()
    {
        if (!defined('TL_MODE')) {
            define('TL_MODE', $this->getMode());
        }

        define('TL_START', microtime(true));
        define('TL_ROOT', dirname($this->rootDir));
        define('TL_REFERER_ID', $this->getRefererId());

        if (!defined('TL_SCRIPT')) {
            define('TL_SCRIPT', $this->getRoute());
        }

        // Define the login status constants in the back end (see #4099, #5279)
        if (!$this->isFrontendScope()) {
            define('BE_USER_LOGGED_IN', false);
            define('FE_USER_LOGGED_IN', false);
        }

        // Define the relative path to the installation (see #5339)
        define('TL_PATH', $this->getPath());
    }

    /**
     * Returns the TL_MODE value.
     *
     * @return string|null
     */
    private function getMode()
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
     * Returns the referer ID.
     *
     * @return string|null
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
     * @return string|null
     */
    private function getRoute()
    {
        if (null === $this->request) {
            return null;
        }

        $attributes = $this->request->attributes;

        try {
            $route = $this->router->generate($attributes->get('_route'), $attributes->get('_route_params'));
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return substr($route, strlen($this->request->getBasePath()) + 1);
    }

    /**
     * Returns the base path.
     *
     * @return string|null
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
        $this->includeBasicClasses();

        // Set the container
        System::setContainer($this->container);

        /** @var Config $config */
        $config = $this->getAdapter(Config::class);

        // Preload the configuration (see #5872)
        $config->preload();

        // Register the class loader
        ClassLoader::scanAndRegister();

        $this->initializeLegacySessionAccess();
        $this->setDefaultLanguage();

        // Fully load the configuration
        $config->getInstance();

        $this->validateInstallation();

        Input::initialize();
        TemplateLoader::initialize();

        $this->setTimezone();
        $this->triggerInitializeSystemHook();
        $this->handleRequestToken();
    }

    /**
     * Includes some helper files.
     */
    private function includeHelpers()
    {
        require __DIR__.'/../Resources/contao/helper/functions.php';
        require __DIR__.'/../Resources/contao/config/constants.php';
        require __DIR__.'/../Resources/contao/helper/interface.php';
        require __DIR__.'/../Resources/contao/helper/exception.php';
    }

    /**
     * Includes the basic classes required for further processing.
     */
    private function includeBasicClasses()
    {
        foreach ($this->basicClasses as $class) {
            if (!class_exists($class, false)) {
                require_once __DIR__.'/../Resources/contao/library/Contao/'.$class.'.php';
                class_alias('Contao\\'.$class, $class);
            }
        }
    }

    /**
     * Initializes session access for $_SESSION['FE_DATA'] and $_SESSION['BE_DATA'].
     */
    private function initializeLegacySessionAccess()
    {
        if (!$this->session->isStarted()) {
            return;
        }

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

        // Deprecated since Contao 4.0, to be removed in Contao 5.0
        $GLOBALS['TL_LANGUAGE'] = $language;
        $_SESSION['TL_LANGUAGE'] = $language;
    }

    /**
     * Validates the installation.
     *
     * @throws IncompleteInstallationException If the installation has not been completed
     */
    private function validateInstallation()
    {
        if (null === $this->request) {
            return;
        }

        /** @var Config $config */
        $config = $this->getAdapter(Config::class);

        // Show the "incomplete installation" message
        if (!$config->isComplete()) {
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
        /** @var Config $config */
        $config = $this->getAdapter(Config::class);

        $this->iniSet('date.timezone', $config->get('timeZone'));
        date_default_timezone_set($config->get('timeZone'));
    }

    /**
     * Triggers the initializeSystem hook (see #5665).
     */
    private function triggerInitializeSystemHook()
    {
        if (isset($GLOBALS['TL_HOOKS']['initializeSystem']) && is_array($GLOBALS['TL_HOOKS']['initializeSystem'])) {
            foreach ($GLOBALS['TL_HOOKS']['initializeSystem'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}();
            }
        }

        if (file_exists($this->rootDir.'/../system/config/initconfig.php')) {
            @trigger_error('Using the initconfig.php file has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);
            include $this->rootDir.'/../system/config/initconfig.php';
        }
    }

    /**
     * Handles the request token.
     *
     * @throws AjaxRedirectResponseException|InvalidRequestTokenException
     */
    private function handleRequestToken()
    {
        /** @var RequestToken $requestToken */
        $requestToken = $this->getAdapter(RequestToken::class);

        // Deprecated since Contao 4.0, to be removed in Contao 5.0
        if (!defined('REQUEST_TOKEN')) {
            define('REQUEST_TOKEN', $requestToken->get());
        }

        if ($this->canSkipTokenCheck() || $requestToken->validate($this->request->request->get('REQUEST_TOKEN'))) {
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
     * @param string $key
     * @param mixed  $value
     */
    private function iniSet($key, $value)
    {
        if (function_exists('ini_set')) {
            ini_set($key, $value);
        }
    }

    /**
     * Checks if the token check can be skipped.
     *
     * @return bool True
     */
    private function canSkipTokenCheck()
    {
        return null === $this->request
            || 'POST' !== $this->request->getRealMethod()
            || !$this->request->attributes->has('_token_check')
            || false === $this->request->attributes->get('_token_check')
        ;
    }
}
