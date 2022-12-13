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

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Environment;
use Contao\Input;
use Contao\InsertTags;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Contao\TemplateLoader;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal Do not use this class in your code; use the "contao.framework" service instead
 */
class ContaoFramework implements ContainerAwareInterface, ResetInterface
{
    use ContainerAwareTrait;

    private static bool $initialized = false;
    private static string $nonce = '';

    private Request|null $request = null;
    private array $adapterCache = [];
    private array $hookListeners = [];

    public function __construct(
        private RequestStack $requestStack,
        private string $projectDir,
        private int $errorLevel,
    ) {
    }

    public function reset(): void
    {
        $this->adapterCache = [];
        self::$nonce = '';

        if (!$this->isInitialized()) {
            return;
        }

        Controller::resetControllerCache();
        Environment::reset();
        Input::setUnusedRouteParameters([]);
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

        $this->request = $this->requestStack->getCurrentRequest();

        $this->initializeFramework();
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
    public function createInstance(string $class, array $args = [])
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
    public function getAdapter(string $class): Adapter
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

    private function initializeFramework(): void
    {
        // Set the error_reporting level
        error_reporting($this->errorLevel);

        $this->includeBasicClasses();

        // Set the container
        System::setContainer($this->container);

        $config = $this->getAdapter(Config::class);

        // Preload the configuration (see #5872)
        $config->preload();

        $this->setDefaultLanguage();

        // Fully load the configuration
        $config->getInstance();

        $this->registerHookListeners();

        Input::initialize();
        TemplateLoader::initialize();

        $this->setTimezone();
        $this->triggerInitializeSystemHook();
    }

    /**
     * Includes the basic classes required for further processing.
     */
    private function includeBasicClasses(): void
    {
        static $basicClasses = [
            'System',
            'Config',
            'TemplateLoader',
        ];

        foreach ($basicClasses as $class) {
            if (!class_exists($class, false)) {
                require_once __DIR__.'/../../contao/library/Contao/'.$class.'.php';
            }
        }
    }

    private function setDefaultLanguage(): void
    {
        $language = 'en';

        if (null !== $this->request) {
            $language = LocaleUtil::formatAsLanguageTag($this->request->getLocale());
        }

        // Deprecated since Contao 4.0, to be removed in Contao 6.0
        $GLOBALS['TL_LANGUAGE'] = $language;
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
    }

    private function iniSet(string $key, string $value): void
    {
        if (\function_exists('ini_set')) {
            ini_set($key, $value);
        }
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
