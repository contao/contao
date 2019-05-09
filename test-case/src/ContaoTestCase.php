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

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class ContaoTestCase extends TestCase
{
    /**
     * @var array
     */
    private static $tempDirs = [];

    /**
     * {@inheritdoc}
     */
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
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.default_locale', 'en');
        $container->setParameter('kernel.cache_dir', $projectDir.'/var/cache');
        $container->setParameter('kernel.project_dir', $projectDir);
        $container->setParameter('kernel.root_dir', $projectDir.'/app');

        // Load the default configuration
        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

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
    protected function mockContaoFramework(array $adapters = []): ContaoFramework
    {
        $this->addConfigAdapter($adapters);

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                static function (string $key) use ($adapters): ?Adapter {
                    return $adapters[$key] ?? null;
                }
            )
        ;

        return $framework;
    }

    /**
     * Mocks an adapter with the given methods.
     *
     * @return Adapter&MockObject
     */
    protected function mockAdapter(array $methods): Adapter
    {
        return $this->createPartialMock(Adapter::class, $methods);
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
            $adapter->method($method)->willReturn($return);
        }

        return $adapter;
    }

    /**
     * Mocks a class with magic properties.
     */
    protected function mockClassWithProperties(string $class, array $properties = []): MockObject
    {
        $mock = $this->createMock($class);
        $mock
            ->method('__get')
            ->willReturnCallback(
                static function (string $key) use (&$properties) {
                    return $properties[$key] ?? null;
                }
            )
        ;

        if (\in_array('__set', get_class_methods($class), true)) {
            $mock
                ->method('__set')
                ->willReturnCallback(
                    static function (string $key, $value) use (&$properties) {
                        $properties[$key] = $value;
                    }
                )
            ;
        }

        if (\in_array('__isset', get_class_methods($class), true)) {
            $mock
                ->method('__isset')
                ->willReturnCallback(
                    static function (string $key) use (&$properties) {
                        return isset($properties[$key]);
                    }
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

        $user = $this->createPartialMock($class, ['hasAccess']);
        $user
            ->method('hasAccess')
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
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
            ->willReturnCallback(
                static function (string $key) {
                    return $GLOBALS['TL_CONFIG'][$key] ?? null;
                }
            )
        ;

        $adapters[Config::class] = $adapter;
    }

    /**
     * Loads the default configuration from the Contao core bundle.
     */
    private function loadDefaultConfiguration(): void
    {
        switch (true) {
            // The core-bundle is in the vendor folder of the monorepo
            case file_exists(__DIR__.'/../../../../core-bundle/src/Resources/contao/config/default.php'):
                include __DIR__.'/../../../../core-bundle/src/Resources/contao/config/default.php';
                break;

            // The core-bundle is in the vendor folder of the managed edition
            case file_exists(__DIR__.'/../../../../../core-bundle/src/Resources/contao/config/default.php'):
                include __DIR__.'/../../../../../core-bundle/src/Resources/contao/config/default.php';
                break;

            // The core-bundle is the root package and the test-case folder is in vendor/contao
            case file_exists(__DIR__.'/../../../../src/Resources/contao/config/default.php'):
                include __DIR__.'/../../../../src/Resources/contao/config/default.php';
                break;

            // Another bundle is the root package and the core-bundle folder is in vendor/contao
            case file_exists(__DIR__.'/../../core-bundle/src/Resources/contao/config/default.php'):
                include __DIR__.'/../../core-bundle/src/Resources/contao/config/default.php';
                break;

            // The test-case is the root package and the core-bundle folder is in vendor/contao
            case file_exists(__DIR__.'/../vendor/contao/core-bundle/src/Resources/contao/config/default.php'):
                include __DIR__.'/../vendor/contao/core-bundle/src/Resources/contao/config/default.php';
                break;

            default:
                throw new \RuntimeException('Cannot find the Contao configuration file');
        }
    }
}
