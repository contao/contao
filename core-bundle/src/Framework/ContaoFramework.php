<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Framework;

use Contao\ClassLoader;
use Contao\Config;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Session\LazySessionAccess;
use Contao\Input;
use Contao\RequestToken;
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal Do not instantiate this class in your code; use the "contao.framework" service instead
 */
class ContaoFramework implements ContaoFrameworkInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var bool
     */
    private static $initialized = false;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var int
     */
    private $errorLevel;

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
    private $hookListeners = [];

    /**
     * @param RequestStack|null $requestStack Deprecated since Contao 4.7, to be removed in Contao 5.0
     */
    public function __construct(?RequestStack $requestStack, RouterInterface $router, ScopeMatcher $scopeMatcher, string $rootDir, int $errorLevel)
    {
        if (null !== $requestStack) {
            @trigger_error('Injecting the request stack in the Contao framework is no longer supported since Contao 4.7. Use ContaoFramework::setRequest() instead.', E_USER_DEPRECATED);
        }

        $this->router = $router;
        $this->scopeMatcher = $scopeMatcher;
        $this->rootDir = $rootDir;
        $this->errorLevel = $errorLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    public function initialize(): void
    {
        if ($this->isInitialized()) {
            return;
        }

        // Set before calling any methods to prevent recursion
        self::$initialized = true;

        if (null === $this->container) {
            throw new \LogicException('The service container has not been set.');
        }

        \define('TL_ROOT', $this->rootDir);

        $this->setConstants();
        $this->initializeFramework();
    }

    public function setHookListeners(array $hookListeners): void
    {
        $this->hookListeners = $hookListeners;
    }

    public function setRequest(Request $request): void
    {
        // Do not overwrite the request in a sub-request. Unfortunately, the master
        // request might be cached, so the only request we have is a sub-request.
        // Therefore we just hopefully assume the first request is a master request.
        if (null !== $this->request) {
            return;
        }

        $this->request = $request;

        if (!$this->isInitialized()) {
            return;
        }

        $this->setConstants();
        $this->initializeLegacySessionAccess();
        $this->setDefaultLanguage();
        $this->validateInstallation();
        $this->handleRequestToken();
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance($class, $args = [])
    {
        if (\in_array('getInstance', get_class_methods($class), true)) {
            return \call_user_func_array([$class, 'getInstance'], $args);
        }

        $reflection = new \ReflectionClass($class);

        return $reflection->newInstanceArgs($args);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdapter($class): Adapter
    {
        if (!isset($this->adapterCache[$class])) {
            $this->adapterCache[$class] = new Adapter($class);
        }

        return $this->adapterCache[$class];
    }

    /**
     * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0
     */
    private function setConstants(): void
    {
        if (null === $this->request) {
            return;
        }

        \define('TL_MODE', $this->getMode($this->request));
        \define('TL_REFERER_ID', $this->request->attributes->get('_contao_referer_id', ''));
        \define('TL_SCRIPT', $this->getRoute($this->request));

        // Define the login status constants in the back end (see #4099, #5279)
        if (!$this->scopeMatcher->isFrontendRequest($this->request)) {
            \define('BE_USER_LOGGED_IN', false);
            \define('FE_USER_LOGGED_IN', false);
        }

        // Define the relative path to the installation (see #5339)
        \define('TL_PATH', $this->request->getBasePath());
    }

    private function getMode(Request $request): ?string
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return 'BE';
        }

        if ($this->scopeMatcher->isFrontendRequest($request)) {
            return 'FE';
        }

        return null;
    }

    private function getRoute(Request $request): ?string
    {
        $attributes = $request->attributes;

        if (!$attributes->has('_route')) {
            return null;
        }

        try {
            $route = $this->router->generate($attributes->get('_route'), $attributes->get('_route_params'));

            // The Symfony router can return null even though the interface only allows strings
            if (!\is_string($route)) {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }

        $basePath = $request->getBasePath().'/';

        if (0 !== strncmp($route, $basePath, \strlen($basePath))) {
            return null;
        }

        return substr($route, \strlen($basePath));
    }

    private function initializeFramework(): void
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

        $this->registerHookListeners();
        $this->validateInstallation();

        Input::initialize();
        TemplateLoader::initialize();

        $this->setTimezone();
        $this->triggerInitializeSystemHook();
        $this->handleRequestToken();
    }

    private function includeHelpers(): void
    {
        require __DIR__.'/../Resources/contao/helper/functions.php';
        require __DIR__.'/../Resources/contao/config/constants.php';
    }

    /**
     * Includes the basic classes required for further processing.
     */
    private function includeBasicClasses(): void
    {
        static $basicClasses = [
            'System',
            'Config',
            'ClassLoader',
            'TemplateLoader',
            'ModuleLoader',
        ];

        foreach ($basicClasses as $class) {
            if (!class_exists($class, false)) {
                require_once __DIR__.'/../Resources/contao/library/Contao/'.$class.'.php';
            }
        }
    }

    /**
     * Initializes session access for $_SESSION['FE_DATA'] and $_SESSION['BE_DATA'].
     */
    private function initializeLegacySessionAccess(): void
    {
        if (null === $this->request || !$this->request->hasSession()) {
            return;
        }

        $session = $this->request->getSession();

        if (!$session->isStarted()) {
            $_SESSION = new LazySessionAccess($session);
        } else {
            $_SESSION['BE_DATA'] = $session->getBag('contao_backend');
            $_SESSION['FE_DATA'] = $session->getBag('contao_frontend');
        }
    }

    private function setDefaultLanguage(): void
    {
        $language = 'en';

        if (null !== $this->request) {
            $language = str_replace('_', '-', $this->request->getLocale());
        }

        // Deprecated since Contao 4.0, to be removed in Contao 5.0
        $GLOBALS['TL_LANGUAGE'] = $language;
    }

    /**
     * @throws IncompleteInstallationException
     */
    private function validateInstallation(): void
    {
        if (null === $this->request) {
            return;
        }

        static $installRoutes = [
            'contao_install',
            'contao_install_redirect',
        ];

        if (\in_array($this->request->attributes->get('_route'), $installRoutes, true)) {
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

    private function setTimezone(): void
    {
        /** @var Config $config */
        $config = $this->getAdapter(Config::class);

        $this->iniSet('date.timezone', (string) $config->get('timeZone'));
        date_default_timezone_set((string) $config->get('timeZone'));
    }

    private function triggerInitializeSystemHook(): void
    {
        if (isset($GLOBALS['TL_HOOKS']['initializeSystem']) && \is_array($GLOBALS['TL_HOOKS']['initializeSystem'])) {
            foreach ($GLOBALS['TL_HOOKS']['initializeSystem'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}();
            }
        }

        if (file_exists($this->rootDir.'/system/config/initconfig.php')) {
            @trigger_error('Using the initconfig.php file has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);
            include $this->rootDir.'/system/config/initconfig.php';
        }
    }

    /**
     * @throws InvalidRequestTokenException
     */
    private function handleRequestToken(): void
    {
        /** @var RequestToken $requestToken */
        $requestToken = $this->getAdapter(RequestToken::class);

        // Deprecated since Contao 4.0, to be removed in Contao 5.0
        if (!\defined('REQUEST_TOKEN')) {
            \define('REQUEST_TOKEN', 'cli' === \PHP_SAPI ? null : $requestToken->get());
        }

        if ($this->canSkipTokenCheck() || $requestToken->validate($this->request->request->get('REQUEST_TOKEN'))) {
            return;
        }

        throw new InvalidRequestTokenException('Invalid request token. Please reload the page and try again.');
    }

    private function iniSet(string $key, string $value): void
    {
        if (\function_exists('ini_set')) {
            ini_set($key, $value);
        }
    }

    private function canSkipTokenCheck(): bool
    {
        return null === $this->request
            || 'POST' !== $this->request->getRealMethod()
            || $this->request->isXmlHttpRequest()
            || !$this->request->attributes->has('_token_check')
            || false === $this->request->attributes->get('_token_check')
        ;
    }

    private function registerHookListeners(): void
    {
        foreach ($this->hookListeners as $hookName => $priorities) {
            if (isset($GLOBALS['TL_HOOKS'][$hookName]) && \is_array($GLOBALS['TL_HOOKS'][$hookName])) {
                if (isset($priorities[0])) {
                    $priorities[0] = array_merge($GLOBALS['TL_HOOKS'][$hookName], $priorities[0]);
                } else {
                    $priorities[0] = $GLOBALS['TL_HOOKS'][$hookName];
                    krsort($priorities);
                }
            }

            $GLOBALS['TL_HOOKS'][$hookName] = array_merge(...$priorities);
        }
    }
}
