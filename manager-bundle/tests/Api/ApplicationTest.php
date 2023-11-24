<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Api;

use Contao\ManagerBundle\Api\Application;
use Contao\ManagerBundle\Api\ManagerConfig;
use Contao\ManagerBundle\ContaoManager\ApiCommand\GetConfigCommand;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerPlugin\Api\ApiPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Contao\TestCase\ContaoTestCase;

class ApplicationTest extends ContaoTestCase
{
    public function testReturnsCorrectApplicationNameAndVersion(): void
    {
        $application = $this->getApplication();

        $this->assertSame('contao-api', $application->getName());
        $this->assertSame(Application::VERSION, $application->getVersion());
    }

    public function testReturnsProjectDir(): void
    {
        $application = $this->getApplication('/foo/bar');

        $this->assertSame('/foo/bar', $application->getProjectDir());
    }

    public function testReturnsConfiguredPluginLoader(): void
    {
        $pluginLoader = $this->createMock(PluginLoader::class);

        $application = $this->getApplication();
        $application->setPluginLoader($pluginLoader);

        $this->assertSame($pluginLoader, $application->getPluginLoader());
    }

    public function testSetsDisabledPackagesInPluginLoader(): void
    {
        $config = $this->createMock(ManagerConfig::class);
        $config
            ->expects($this->once())
            ->method('all')
            ->willReturn([
                'contao_manager' => [
                    'disabled_packages' => ['foo/bar'],
                ],
            ])
        ;

        $application = $this->getApplication();
        $application->setManagerConfig($config);

        $pluginLoader = $application->getPluginLoader();

        $this->assertSame(['foo/bar'], $pluginLoader->getDisabledPackages());
    }

    public function testReturnsNewInstanceOfManagerConfigWithProjectDir(): void
    {
        $application = $this->getApplication(__DIR__.'/../Fixtures/Api');
        $managerConfig = $application->getManagerConfig();

        $this->assertSame(['foo' => 'bar'], $managerConfig->all());
    }

    public function testReturnsConfiguredManagerConfig(): void
    {
        $managerConfig = $this->createMock(ManagerConfig::class);

        $application = $this->getApplication();
        $application->setManagerConfig($managerConfig);

        $this->assertSame($managerConfig, $application->getManagerConfig());
    }

    public function testGetsCommandsFromPlugins(): void
    {
        $plugin = $this->createMock(ApiPluginInterface::class);
        $plugin
            ->expects($this->once())
            ->method('getApiCommands')
            ->willReturn([GetConfigCommand::class])
        ;

        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(ApiPluginInterface::class)
            ->willReturn([$plugin])
        ;

        $application = $this->getApplication();
        $application->setPluginLoader($pluginLoader);

        $commands = $application->all();

        $this->assertArrayHasKey('config:get', $commands);
    }

    public function testThrowsExceptionIfPluginReturnsInvalidCommand(): void
    {
        $plugin = $this->createMock(ApiPluginInterface::class);
        $plugin
            ->expects($this->once())
            ->method('getApiCommands')
            ->willReturn([ContaoManagerBundle::class])
        ;

        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(ApiPluginInterface::class)
            ->willReturn([$plugin])
        ;

        $application = $this->getApplication();
        $application->setPluginLoader($pluginLoader);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('"Contao\ManagerBundle\ContaoManagerBundle" is not a console command.');

        $application->all();
    }

    private function getApplication(string|null $path = null): Application
    {
        return new Application($path ?? $this->getTempDir());
    }
}
