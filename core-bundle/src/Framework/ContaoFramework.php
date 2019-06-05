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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Session\LazySessionAccess;
use Contao\Input;
use Contao\RequestToken;
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var TokenChecker
     */
    private $tokenChecker;

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
     * @var bool
     */
    private $isFrontend = false;

    /**
     * @var array
     */
    private $adapterCache = [];

    /**
     * @var array
     */
    private $hookListeners = [];

    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher, TokenChecker $tokenChecker, string $rootDir, int $errorLevel)
    {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenChecker = $tokenChecker;
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
    public function initialize(bool $isFrontend = false): void
    {
        if ($this->isInitialized()) {
            return;
        }

        // Set before calling any methods to prevent recursion
        self::$initialized = true;

        if (null === $this->container) {
            throw new \LogicException('The service container has not been set.');
        }

        $this->isFrontend = $isFrontend;
        $this->request = $this->requestStack->getMasterRequest();

        $this->setConstants();
        $this->initializeFramework();
    }

    public function setHookListeners(array $hookListeners): void
    {
        $this->hookListeners = $hookListeners;
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
        if (!\defined('TL_MODE')) {
            \define('TL_MODE', $this->getMode());
        }

        \define('TL_START', microtime(true));
        \define('TL_ROOT', $this->rootDir);
        \define('TL_REFERER_ID', $this->getRefererId());

        if (!\defined('TL_SCRIPT')) {
            \define('TL_SCRIPT', $this->getRoute());
        }

        // Define the login status constants (see #4099, #5279)
        if ('FE' === $this->getMode() && ($session = $this->getSession()) && $this->request->hasPreviousSession()) {
            $session->start();

            \define('BE_USER_LOGGED_IN', $this->tokenChecker->hasBackendUser() && $this->tokenChecker->isPreviewMode());
            \define('FE_USER_LOGGED_IN', $this->tokenChecker->hasFrontendUser());
        } else {
            \define('BE_USER_LOGGED_IN', false);
            \define('FE_USER_LOGGED_IN', false);
        }

        // Define the relative path to the installation (see #5339)
        \define('TL_PATH', $this->getPath());
    }

    private function getMode(): ?string
    {
        if (true === $this->isFrontend) {
            return 'FE';
        }

        if (null === $this->request) {
            return null;
        }

        if ($this->scopeMatcher->isBackendRequest($this->request)) {
            return 'BE';
        }

        if ($this->scopeMatcher->isFrontendRequest($this->request)) {
            return 'FE';
        }

        return null;
    }

    private function getRefererId(): ?string
    {
        if (null === $this->request) {
            return null;
        }

        return $this->request->attributes->get('_contao_referer_id', '');
    }

    private function getRoute(): ?string
    {
        if (null === $this->request) {
            return null;
        }

        return substr($this->request->getBaseUrl().$this->request->getPathInfo(), \strlen($this->request->getBasePath().'/'));
    }

    private function getPath(): ?string
    {
        if (null === $this->request) {
            return null;
        }

        return $this->request->getBasePath();
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
        if (!$session = $this->getSession()) {
            return;
        }

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

    private function handleRequestToken(): void
    {
        /** @var RequestToken $requestToken */
        $requestToken = $this->getAdapter(RequestToken::class);

        // Deprecated since Contao 4.0, to be removed in Contao 5.0
        if (!\defined('REQUEST_TOKEN')) {
            \define('REQUEST_TOKEN', 'cli' === \PHP_SAPI ? null : $requestToken->get());
        }
    }

    private function iniSet(string $key, string $value): void
    {
        if (\function_exists('ini_set')) {
            ini_set($key, $value);
        }
    }

    private function getSession(): ?SessionInterface
    {
        if (null === $this->request || !$this->request->hasSession()) {
            return null;
        }

        return $this->request->getSession();
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
