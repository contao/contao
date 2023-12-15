<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\TestCase;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class ContaoTestCase extends TestCase
{
    private static array $tempDirs = [];

    private array $backupServerEnvGetPost = [];

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $key = basename(strtr(static::class, '\\', '/'));

        if (!isset(self::$tempDirs[$key])) {
            return;
        }

        $fs = new Filesystem();

        if ($fs->exists(self::$tempDirs[$key])) {
            $fs->remove(self::$tempDirs[$key]);
        }

        unset(self::$tempDirs[$key]);
    }

    protected function tearDown(): void
    {
        // Unset TL_CONFIG as we populate it in loadDefaultConfiguration (see #4656)
        unset($GLOBALS['TL_CONFIG']);

        parent::tearDown();
    }

    /**
     * Returns the path to the temporary directory and creates it if it does not yet exist.
     */
    protected static function getTempDir(): string
    {
        $key = basename(strtr(static::class, '\\', '/'));

        if (!isset(self::$tempDirs[$key])) {
            self::$tempDirs[$key] = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid($key.'_', true);

            $fs = new Filesystem();

            if (!$fs->exists(self::$tempDirs[$key])) {
                $fs->mkdir(self::$tempDirs[$key]);
            }
        }

        return self::$tempDirs[$key];
    }

    /**
     * Returns a Symfony container with the Contao core extension configuration.
     */
    protected function getContainerWithContaoConfiguration(string $projectDir = ''): ContainerBuilder
    {
        static $cachedContainers = [];

        if (!isset($cachedContainers[$projectDir])) {
            $cachedContainers[$projectDir] = new ContainerBuilder();
            $cachedContainers[$projectDir]->setParameter('kernel.debug', false);
            $cachedContainers[$projectDir]->setParameter('kernel.charset', 'UTF-8');
            $cachedContainers[$projectDir]->setParameter('kernel.default_locale', 'en');
            $cachedContainers[$projectDir]->setParameter('kernel.cache_dir', $projectDir.'/var/cache');
            $cachedContainers[$projectDir]->setParameter('kernel.project_dir', $projectDir);
            $cachedContainers[$projectDir]->setParameter('kernel.root_dir', $projectDir.'/app');
            $cachedContainers[$projectDir]->setDefinition('request_stack', new Definition(RequestStack::class));

            // Load the default configuration
            $extension = new ContaoCoreExtension();
            $extension->load([], $cachedContainers[$projectDir]);
        }

        $container = new ContainerBuilder();
        $container->merge($cachedContainers[$projectDir]);
        $container->set('parameter_bag', new ContainerBag($container));

        return $container;
    }

    /**
     * Mocks the Contao framework with optional adapters.
     *
     * A Config adapter with the default Contao configuration will be added
     * automatically if no Config adapter is given.
     *
     * @return ContaoFramework&MockObject
     */
    protected function mockContaoFramework(array $adapters = [], array $instances = []): ContaoFramework
    {
        $this->addConfigAdapter($adapters);

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(static fn (string $key): Adapter|null => $adapters[$key] ?? null)
        ;

        if ($instances) {
            $framework
                ->method('createInstance')
                ->willReturnCallback(
                    static function (string $key) use ($instances): mixed {
                        if (!isset($instances[$key])) {
                            return null;
                        }

                        if ($instances[$key] instanceof \Closure) {
                            return $instances[$key]();
                        }

                        return $instances[$key];
                    },
                )
            ;
        }

        return $framework;
    }

    /**
     * Mocks an adapter with the given methods.
     *
     * @return Adapter&MockObject
     */
    protected function mockAdapter(array $methods): Adapter
    {
        return $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->addMethods($methods)
            ->getMock()
        ;
    }

    /**
     * Mocks a configured adapter with the given methods and return values.
     *
     * @return Adapter&MockObject
     */
    protected function mockConfiguredAdapter(array $configuration): Adapter
    {
        $adapter = $this->mockAdapter(array_keys($configuration));

        foreach ($configuration as $method => $return) {
            $adapter
                ->method($method)
                ->willReturn($return)
            ;
        }

        return $adapter;
    }

    /**
     * Mocks a class with magic properties.
     *
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T&MockObject
     */
    protected function mockClassWithProperties(string $class, array $properties = [], array $except = []): MockObject
    {
        $classMethods = get_class_methods($class);

        if (!$except) {
            $mock = $this->createMock($class);
        } else {
            $mock = $this->createPartialMock($class, array_diff($classMethods, $except));
        }

        $mock
            ->method('__get')
            ->willReturnCallback(
                static function (string $key) use (&$properties) {
                    return $properties[$key] ?? null;
                },
            )
        ;

        if (\in_array('__set', $classMethods, true)) {
            $mock
                ->method('__set')
                ->willReturnCallback(
                    static function (string $key, $value) use (&$properties): void {
                        $properties[$key] = $value;
                    },
                )
            ;
        }

        if (\in_array('__isset', $classMethods, true)) {
            $mock
                ->method('__isset')
                ->willReturnCallback(
                    static function (string $key) use (&$properties) {
                        return isset($properties[$key]);
                    },
                )
            ;
        }

        if (\in_array('row', $classMethods, true)) {
            $mock
                ->method('row')
                ->willReturnCallback(
                    static function () use (&$properties) {
                        return $properties;
                    },
                )
            ;
        }

        if (\in_array('setRow', $classMethods, true)) {
            $mock
                ->method('setRow')
                ->willReturnCallback(
                    static function (array $data) use (&$properties): void {
                        $properties = $data;
                    },
                )
            ;
        }

        return $mock;
    }

    /**
     * Mocks a token storage with a Contao user.
     */
    protected function mockTokenStorage(string $class): TokenStorageInterface
    {
        if (!is_a($class, User::class, true)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" is not a Contao\User class', $class));
        }

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($this->createMock($class))
        ;

        $token
            ->method('getRoleNames')
            ->willReturn([is_a($class, BackendUser::class, true) ? 'ROLE_USER' : 'ROLE_MEMBER'])
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
    }

    protected function backupServerEnvGetPost(): void
    {
        $this->backupServerEnvGetPost = [
            '_SERVER' => $_SERVER,
            '_ENV' => $_ENV,
            '_GET' => $_GET,
            '_POST' => $_POST,
        ];
    }

    protected function restoreServerEnvGetPost(): void
    {
        $_SERVER = $this->backupServerEnvGetPost['_SERVER'] ?? $_SERVER;
        $_ENV = $this->backupServerEnvGetPost['_ENV'] ?? $_ENV;
        $_GET = $this->backupServerEnvGetPost['_GET'] ?? [];
        $_POST = $this->backupServerEnvGetPost['_POST'] ?? [];
    }

    /**
     * @param array<int, class-string|array{0: class-string, 1: array<int, string>}> $classNames
     */
    protected function resetStaticProperties(array $classNames): void
    {
        foreach ($classNames as $class) {
            $properties = null;

            if (\is_array($class)) {
                $properties = $class[1];
                $class = $class[0];
            }

            if (!class_exists($class, false)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($class);

            if (
                null === $properties
                && \is_callable([$class, 'reset'])
                && method_exists($class, 'reset')
                && $reflectionClass->getMethod('reset')->isStatic()
                && $reflectionClass->getMethod('reset')->getDeclaringClass()->getName() === $class
                && [] === $reflectionClass->getMethod('reset')->getParameters()
            ) {
                $class::reset();

                continue;
            }

            foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_STATIC) as $property) {
                if (null !== $properties && !\in_array($property->getName(), $properties, true)) {
                    continue;
                }

                if ($property->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                if (!$property->isInitialized()) {
                    continue;
                }

                $defaultValue = $property->getDefaultValue();

                if (!$property->hasDefaultValue() || $property->getValue() === $defaultValue) {
                    continue;
                }

                $property->setValue(null, $defaultValue);
            }
        }
    }

    /**
     * Adds the Config adapter if not present.
     */
    private function addConfigAdapter(array &$adapters): void
    {
        if (isset($adapters[Config::class])) {
            return;
        }

        $this->loadDefaultConfiguration();

        $adapter = $this->mockAdapter(['isComplete', 'get']);
        $adapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $adapter
            ->method('get')
            ->willReturnCallback(static fn (string $key) => $GLOBALS['TL_CONFIG'][$key] ?? null)
        ;

        $adapters[Config::class] = $adapter;
    }

    /**
     * Loads the default configuration from the Contao core bundle.
     */
    private function loadDefaultConfiguration(): void
    {
        match (true) {
            // The core-bundle is in the vendor folder of the monorepo
            file_exists(__DIR__.'/../../../../core-bundle/contao/config/default.php') => include __DIR__.'/../../../../core-bundle/contao/config/default.php',

            // The test-case is in the vendor-bin folder
            file_exists(__DIR__.'/../../../../../../core-bundle/contao/config/default.php') => include __DIR__.'/../../../../../../core-bundle/contao/config/default.php',

            // The core-bundle is in the vendor folder of the managed edition
            file_exists(__DIR__.'/../../../../../core-bundle/contao/config/default.php') => include __DIR__.'/../../../../../core-bundle/contao/config/default.php',

            // The core-bundle is the root package and the test-case folder is in vendor/contao
            file_exists(__DIR__.'/../../../../contao/config/default.php') => include __DIR__.'/../../../../contao/config/default.php',

            // Another bundle is the root package and the core-bundle folder is in vendor/contao
            file_exists(__DIR__.'/../../core-bundle/contao/config/default.php') => include __DIR__.'/../../core-bundle/contao/config/default.php',

            // The test-case is the root package and the core-bundle folder is in vendor/contao
            file_exists(__DIR__.'/../vendor/contao/core-bundle/contao/config/default.php') => include __DIR__.'/../vendor/contao/core-bundle/contao/config/default.php',

            default => throw new \RuntimeException('Cannot find the Contao configuration file'),
        };
    }
}
