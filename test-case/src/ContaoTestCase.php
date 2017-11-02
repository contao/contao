<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\TestCase;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\User;
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
     *
     * @return string
     */
    protected static function getTempDir(): string
    {
        $key = basename(strtr(static::class, '\\', '/'));

        if (!isset(self::$tempDirs[$key])) {
            self::$tempDirs[$key] = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid($key.'_');

            $fs = new Filesystem();

            if (!$fs->exists(self::$tempDirs[$key])) {
                $fs->mkdir(self::$tempDirs[$key]);
            }
        }

        return self::$tempDirs[$key];
    }

    /**
     * Mocks a Symfony container and loads the Contao core extension configuration.
     *
     * @param string $projectDir
     *
     * @return ContainerBuilder
     */
    protected function mockContainer(string $projectDir = ''): ContainerBuilder
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
     * @param array $adapters
     *
     * @return ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockContaoFramework(array $adapters = []): ContaoFrameworkInterface
    {
        $this->addConfigAdapter($adapters);

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($adapters): ?Adapter {
                    return $adapters[$key] ?? null;
                }
            )
        ;

        return $framework;
    }

    /**
     * Mocks an adapter with the given methods.
     *
     * @param array $methods
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockAdapter(array $methods): Adapter
    {
        return $this->createPartialMock(Adapter::class, $methods);
    }

    /**
     * Mocks a configured adapter with the given methods and return values.
     *
     * @param array $configuration
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockConfiguredAdapter(array $configuration)
    {
        $adapter = $this->mockAdapter(array_keys($configuration));

        foreach ($configuration as $method => $return) {
            $adapter->method($method)->willReturn($return);
        }

        return $adapter;
    }

    /**
     * Mocks a token storage with a back end user.
     *
     * @param string $class
     *
     * @return TokenStorageInterface
     *
     * @throws \Exception
     */
    protected function mockTokenStorage(string $class): TokenStorageInterface
    {
        if (!is_a($class, User::class, true)) {
            throw new \Exception(sprintf('Class "%s" is not a Contao\User class', $class));
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
     *
     * @param array $adapters
     */
    private function addConfigAdapter(array &$adapters)
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
                function (string $key) {
                    return $GLOBALS['TL_CONFIG'][$key] ?? null;
                }
            )
        ;

        $adapters[Config::class] = $adapter;
    }

    /**
     * Loads the default configuration.
     *
     * @throws \Exception
     */
    private function loadDefaultConfiguration()
    {
        $path = __DIR__.'/../../core-bundle/src/Resources/contao/config/default.php';

        // The path is different in the core-bundle itself
        if (!file_exists($path)) {
            $path = __DIR__.'/../../../../src/Resources/contao/config/default.php';
        }

        if (!file_exists($path)) {
            throw new \Exception('Cannot find the Contao configuration file');
        }

        include $path;
    }
}
