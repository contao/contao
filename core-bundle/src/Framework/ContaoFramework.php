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
use Contao\CoreBundle\Exception\LegacyRoutingException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Session\LazySessionAccess;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Environment;
use Contao\Input;
use Contao\InsertTags;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\RequestToken;
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal Do not use this class in your code; use the "contao.framework" service instead
 */
class ContaoFramework implements ContaoFrameworkInterface, ContainerAwareInterface, ResetInterface
{
    use ContainerAwareTrait;

    private static bool $initialized = false;
    private static string $nonce = '';

    private RequestStack $requestStack;
    private ScopeMatcher $scopeMatcher;
    private TokenChecker $tokenChecker;
    private Filesystem $filesystem;
    private UrlGeneratorInterface $urlGenerator;
    private string $projectDir;
    private int $errorLevel;
    private bool $legacyRouting;
    private ?Request $request = null;
    private bool $isFrontend = false;
    private array $adapterCache = [];
    private array $hookListeners = [];
    private bool $setLoginConstantsOnInit = false;

    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher, TokenChecker $tokenChecker, Filesystem $filesystem, UrlGeneratorInterface $urlGenerator, string $projectDir, int $errorLevel, bool $legacyRouting)
    {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenChecker = $tokenChecker;
        $this->filesystem = $filesystem;
        $this->urlGenerator = $urlGenerator;
        $this->projectDir = $projectDir;
        $this->errorLevel = $errorLevel;
        $this->legacyRouting = $legacyRouting;
    }

    public function reset(): void
    {
        $this->adapterCache = [];
        $this->isFrontend = false;
        self::$nonce = '';

        if (!$this->isInitialized()) {
            return;
        }

        Controller::resetControllerCache();
        Environment::reset();
        Input::resetCache();
        Input::resetUnusedGet();
        InsertTags::reset();
        PageModel::reset();
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
        $this->request = $this->requestStack->getMainRequest();

        $this->setConstants();
        $this->initializeFramework();

        if (!$this->legacyRouting) {
            $this->throwOnLegacyRoutingHooks();
        }
    }

    public function setHookListeners(array $hookListeners): void
    {
        $this->hookListeners = $hookListeners;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
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
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return Adapter<T>&T
     *
     * @phpstan-return Adapter<T>
     */
    public function getAdapter($class): Adapter
    {
        return $this->adapterCache[$class] ??= new Adapter($class);
    }

    public static function getNonce(): string
    {
        if ('' === self::$nonce) {
            self::$nonce = bin2hex(random_bytes(16));
        }

        return self::$nonce;
    }

    /**
     * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
     */
    public function setLoginConstants(): void
    {
        // If the framework has not been initialized yet, set the login constants on init (#4968)
        if (!$this->isInitialized()) {
            $this->setLoginConstantsOnInit = true;

            return;
        }

        if ('FE' === $this->getMode()) {
            \define('BE_USER_LOGGED_IN', $this->tokenChecker->isPreviewMode());
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

        // Define the login status constants (see #4099, #5279)
        if ($this->setLoginConstantsOnInit || null === $this->requestStack->getCurrentRequest()) {
            $this->setLoginConstants();
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
            $language = LocaleUtil::formatAsLanguageTag($this->request->getLocale());
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

        if (!$this->getAdapter(Config::class)->isComplete()) {
            throw new RedirectResponseException($this->urlGenerator->generate('contao_install', [], UrlGeneratorInterface::ABSOLUTE_URL));
        }
    }

    private function setTimezone(): void
    {
        $config = $this->getAdapter(Config::class);

        $this->iniSet('date.timezone', (string) $config->get('timeZone'));
        date_default_timezone_set((string) $config->get('timeZone'));
    }

    private function triggerInitializeSystemHook(): void
    {
        if (
            !empty($GLOBALS['TL_HOOKS']['initializeSystem'])
            && \is_array($GLOBALS['TL_HOOKS']['initializeSystem'])
            && is_dir(Path::join($this->projectDir, 'system/tmp'))
        ) {
            foreach ($GLOBALS['TL_HOOKS']['initializeSystem'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}();
            }
        }

        if ($this->filesystem->exists($filePath = Path::join($this->projectDir, 'system/config/initconfig.php'))) {
            trigger_deprecation('contao/core-bundle', '4.0', 'Using the "initconfig.php" file has been deprecated and will no longer work in Contao 5.0.');
            include $filePath;
        }
    }

    private function handleRequestToken(): void
    {
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

    private function throwOnLegacyRoutingHooks(): void
    {
        if (empty($GLOBALS['TL_HOOKS']['getPageIdFromUrl']) && empty($GLOBALS['TL_HOOKS']['getRootPageFromUrl'])) {
            return;
        }

        throw new LegacyRoutingException('Legacy routing is required to support the "getPageIdFromUrl" and "getRootPageFromUrl" hooks. Check the Symfony inspector for more information.');
    }
}
