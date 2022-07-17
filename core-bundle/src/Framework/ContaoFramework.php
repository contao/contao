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
use Contao\Controller;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Session\LazySessionAccess;
use Contao\Environment;
use Contao\Input;
use Contao\InsertTags;
use Contao\Model\Registry;
use Contao\RequestToken;
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal Do not use this class in your code; use the "contao.framework" service instead
 */
class ContaoFramework implements ContaoFrameworkInterface, ContainerAwareInterface, ResetInterface
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $projectDir;

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

    /**
     * @var bool
     */
    private $setLoginConstantsOnInit = false;

    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher, TokenChecker $tokenChecker, Filesystem $filesystem, string $projectDir, int $errorLevel)
    {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenChecker = $tokenChecker;
        $this->filesystem = $filesystem;
        $this->projectDir = $projectDir;
        $this->errorLevel = $errorLevel;
    }

    public function reset(): void
    {
        $this->adapterCache = [];
        $this->isFrontend = false;

        if (!$this->isInitialized()) {
            return;
        }

        Controller::resetControllerCache();
        Environment::reset();
        Input::resetCache();
        Input::resetUnusedGet();
        InsertTags::reset();
        Registry::getInstance()->reset();
    }

    public function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
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

    public function createInstance($class, $args = [])
    {
        if (\in_array('getInstance', get_class_methods($class), true)) {
            return \call_user_func_array([$class, 'getInstance'], $args);
        }

        $reflection = new \ReflectionClass($class);

        return $reflection->newInstanceArgs($args);
    }

    public function getAdapter($class): Adapter
    {
        if (!isset($this->adapterCache[$class])) {
            $this->adapterCache[$class] = new Adapter($class);
        }

        return $this->adapterCache[$class];
    }

    /**
     * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
     */
    public function setLoginConstants(Request $request = null): void
    {
        // If the framework has not been initialized yet, set the login constants on init (#4968)
        if (!$this->isInitialized()) {
            $this->setLoginConstantsOnInit = true;

            return;
        }

        if (null !== $request && $this->scopeMatcher->isFrontendRequest($request)) {
            \define('BE_USER_LOGGED_IN', $this->tokenChecker->hasBackendUser() && $this->tokenChecker->isPreviewMode());
            \define('FE_USER_LOGGED_IN', $this->tokenChecker->hasFrontendUser());
        } else {
            \define('BE_USER_LOGGED_IN', false);
            \define('FE_USER_LOGGED_IN', false);
        }
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
        \define('TL_ROOT', $this->projectDir);
        \define('TL_REFERER_ID', $this->getRefererId());

        if (!\defined('TL_SCRIPT')) {
            \define('TL_SCRIPT', $this->getRoute());
        }

        $request = $this->requestStack->getCurrentRequest();

        // Define the login status constants (see #4099, #5279)
        if ($this->setLoginConstantsOnInit || null === $request) {
            $this->setLoginConstants($request);
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
            $_SESSION = new LazySessionAccess($session, $this->request && $this->request->hasPreviousSession());
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
     * Redirects to the install tool if the installation is incomplete.
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

        if (!$config->isComplete()) {
            throw new RedirectResponseException('/contao/install');
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
        if (
            !empty($GLOBALS['TL_HOOKS']['initializeSystem'])
            && \is_array($GLOBALS['TL_HOOKS']['initializeSystem'])
            && is_dir($this->projectDir.'/system/tmp')
        ) {
            foreach ($GLOBALS['TL_HOOKS']['initializeSystem'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}();
            }
        }

        if ($this->filesystem->exists($this->projectDir.'/system/config/initconfig.php')) {
            @trigger_error('Using the "initconfig.php" file has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);
            include $this->projectDir.'/system/config/initconfig.php';
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
