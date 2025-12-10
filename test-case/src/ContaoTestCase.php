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
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
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

    protected function getContainerWithFixtures(): ContainerBuilder
    {
        $fixturesDir = $this->getFixturesDir();

        $container = $this->getContainerWithContaoConfiguration($fixturesDir);
        $container->set('database_connection', $this->createStub(Connection::class));
        $container->setParameter('contao.resources_paths', Path::join($this->getFixturesDir(), 'vendor/contao/test-bundle/Resources/contao'));

        return $container;
    }

    /**
     * Mocks the Contao framework with optional adapters.
     *
     * A Config adapter with the default Contao configuration will be added
     * automatically if no Config adapter is given.
     *
     * @return ContaoFramework&Stub
     *
     * @deprecated Deprecated since Contao 5.7, to be removed in Contao 6;
     *             use createContaoFrameworkMock() or createContaoFrameworkStub() instead.
     */
    protected function mockContaoFramework(array $adapters = [], array $instances = []): ContaoFramework
    {
        trigger_deprecation('contao/test-case', '5.7', 'Using "ContaoTestCase::mockContaoFramework()" is deprecated and will no longer work in Contao 6. Use "ContaoTestCase::createContaoFrameworkMock()" or "ContaoTestCase::createContaoFrameworkStub()" instead.');

        return $this->createContaoFrameworkMock($adapters, $instances);
    }

    /**
     * Creates a Contao framework mock object with optional adapters.
     *
     * A Config adapter with the default Contao configuration will be added
     * automatically if no Config adapter is given.
     */
    protected function createContaoFrameworkMock(array $adapters = [], array $instances = []): ContaoFramework&MockObject
    {
        return $this->addAdaptersAndInstances($this->createMock(ContaoFramework::class), $adapters, $instances);
    }

    /**
     * Creates a Contao framework stub object with optional adapters.
     *
     * A Config adapter with the default Contao configuration will be added
     * automatically if no Config adapter is given.
     */
    protected function createContaoFrameworkStub(array $adapters = [], array $instances = []): ContaoFramework&Stub
    {
        return $this->addAdaptersAndInstances($this->createStub(ContaoFramework::class), $adapters, $instances);
    }

    /**
     * Mocks an adapter with the given methods.
     *
     * @return Adapter&MockObject
     *
     * @deprecated Deprecated since Contao 5.7, to be removed in Contao 6;
     *             use createAdapterMock() or createAdapterStub() instead.
     */
    protected function mockAdapter(array $methods): Adapter
    {
        trigger_deprecation('contao/test-case', '5.7', 'Using "ContaoTestCase::mockAdapter()" is deprecated and will no longer work in Contao 6. Use "ContaoTestCase::createAdapterMock()" or "ContaoTestCase::createAdapterStub()" instead.');

        return $this->createAdapterMock($methods);
    }

    /**
     * Creates an adapter mock object with the given methods.
     */
    protected function createAdapterMock(array $methods): Adapter&MockObject
    {
        return $this->createMock($this->createAdapterClass($methods));
    }

    /**
     * Creates an adapter stub object with the given methods.
     */
    protected function createAdapterStub(array $methods): Adapter&Stub
    {
        return $this->createStub($this->createAdapterClass($methods));
    }

    /**
     * Mocks a configured adapter with the given methods and return values.
     *
     * @return Adapter&MockObject
     *
     * @deprecated Deprecated since Contao 5.7, to be removed in Contao 6;
     *             use createConfiguredAdapterMock() or createConfiguredAdapterStub() instead.
     */
    protected function mockConfiguredAdapter(array $configuration): Adapter
    {
        trigger_deprecation('contao/test-case', '5.7', 'Using "ContaoTestCase::mockConfiguredAdapter()" is deprecated and will no longer work in Contao 6. Use "ContaoTestCase::createConfiguredAdapterMock()" or "ContaoTestCase::createConfiguredAdapterStub()" instead.');

        return $this->createConfiguredAdapterMock($configuration);
    }

    /**
     * Creates a configured adapter mock object with the given methods and return values.
     */
    protected function createConfiguredAdapterMock(array $configuration): Adapter&MockObject
    {
        $adapter = $this->createAdapterMock(array_keys($configuration));

        foreach ($configuration as $method => $return) {
            $adapter
                ->method($method)
                ->willReturn($return)
            ;
        }

        return $adapter;
    }

    /**
     * Creates a configured adapter stub object with the given methods and return values.
     */
    protected function createConfiguredAdapterStub(array $configuration): Adapter&Stub
    {
        $adapter = $this->createAdapterStub(array_keys($configuration));

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
     *
     * @deprecated Deprecated since Contao 5.7, to be removed in Contao 6;
     *             use createClassWithPropertiesMock() or createClassWithPropertiesStub() instead.
     */
    protected function mockClassWithProperties(string $class, array $properties = [], array $except = []): MockObject|Stub
    {
        trigger_deprecation('contao/test-case', '5.7', 'Using "ContaoTestCase::mockClassWithProperties()" is deprecated and will no longer work in Contao 6. Use "ContaoTestCase::createClassWithPropertiesMock()" or "ContaoTestCase::createClassWithPropertiesStub()" instead.');

        $classMethods = get_class_methods($class);

        if (!$except) {
            $mock = $this->createMock($class);
        } else {
            $mock = $this->createPartialMock($class, array_diff($classMethods, $except));
        }

        return $this->addMethods($mock, $classMethods, $properties);
    }

    /**
     * Creates a mock object of a class with magic properties.
     *
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T&MockObject
     */
    protected function createClassWithPropertiesMock(string $class, array $properties = []): MockObject
    {
        return $this->addMethods($this->createMock($class), get_class_methods($class), $properties);
    }

    /**
     * Creates a stub object of a class with magic properties.
     *
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T&Stub
     */
    protected function createClassWithPropertiesStub(string $class, array $properties = []): Stub
    {
        return $this->addMethods($this->createStub($class), get_class_methods($class), $properties);
    }

    /**
     * Mocks a token storage with a Contao user.
     *
     * @deprecated Deprecated since Contao 5.7, to be removed in Contao 6;
     *             use createTokenStorageStub() instead.
     */
    protected function mockTokenStorage(string $class): TokenStorageInterface&Stub
    {
        trigger_deprecation('contao/test-case', '5.7', 'Using "ContaoTestCase::mockTokenStorage()" is deprecated and will no longer work in Contao 6. Use "ContaoTestCase::createTokenStorageStub()" instead.');

        return $this->createTokenStorageStub($class);
    }

    /**
     * Creates a token storage stub with a Contao user.
     */
    protected function createTokenStorageStub(string $class): TokenStorageInterface&Stub
    {
        if (!is_a($class, User::class, true)) {
            throw new \InvalidArgumentException(\sprintf('Class "%s" is not a Contao\User class', $class));
        }

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($this->createStub($class))
        ;

        $token
            ->method('getRoleNames')
            ->willReturn([is_a($class, BackendUser::class, true) ? 'ROLE_USER' : 'ROLE_MEMBER'])
        ;

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
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

                if (!$property->hasDefaultValue() || $property->getValue() === ($defaultValue = $property->getDefaultValue())) {
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

        $adapter = $this->createAdapterStub(['isComplete', 'get']);
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
     * Creates an adapter with the given methods and returns the class name.
     */
    private function createAdapterClass(array $methods): string
    {
        sort($methods);

        $namespace = 'Contao\DynamicTestClass';
        $className = 'Adapter'.sha1(implode(':', $methods));
        $fqcn = $namespace.'\\'.$className;

        if (!class_exists($fqcn, false)) {
            $class = 'namespace %s; class %s extends \%s { %s }';
            $methods = array_map(static fn (string $method): string => \sprintf('public function %s() {}', $method), $methods);

            eval(\sprintf($class, $namespace, $className, Adapter::class, implode(' ', $methods)));
        }

        return $fqcn;
    }

    private function addAdaptersAndInstances(MockObject|Stub $framework, $adapters, $instances): MockObject|Stub
    {
        $this->addConfigAdapter($adapters);

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

    private function addMethods(MockObject|Stub $object, array $methods, array $properties): MockObject|Stub
    {
        $object
            ->method('__get')
            ->willReturnCallback(
                static function (string $key) use (&$properties) {
                    return $properties[$key] ?? null;
                },
            )
        ;

        if (\in_array('__set', $methods, true)) {
            $object
                ->method('__set')
                ->willReturnCallback(
                    static function (string $key, $value) use (&$properties): void {
                        $properties[$key] = $value;
                    },
                )
            ;
        }

        if (\in_array('__isset', $methods, true)) {
            $object
                ->method('__isset')
                ->willReturnCallback(
                    static function (string $key) use (&$properties) {
                        return isset($properties[$key]);
                    },
                )
            ;
        }

        if (\in_array('row', $methods, true)) {
            $object
                ->method('row')
                ->willReturnCallback(
                    static function () use (&$properties) {
                        return $properties;
                    },
                )
            ;
        }

        if (\in_array('setRow', $methods, true)) {
            $object
                ->method('setRow')
                ->willReturnCallback(
                    static function (array $data) use (&$properties): void {
                        $properties = $data;
                    },
                )
            ;
        }

        return $object;
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
