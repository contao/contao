<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Api\Command;

use Contao\ManagerBundle\Api\Application;
use Contao\ManagerBundle\Api\Command\VersionCommand;
use Contao\ManagerPlugin\Api\ApiPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class VersionCommandTest extends TestCase
{
    /**
     * @var Application&MockObject
     */
    private Application $application;

    /**
     * @var PluginLoader&MockObject
     */
    private PluginLoader $pluginLoader;

    private VersionCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = $this->createMock(Application::class);
        $this->pluginLoader = $this->createMock(PluginLoader::class);

        $this->application
            ->method('getPluginLoader')
            ->willReturn($this->pluginLoader)
        ;

        $this->command = new VersionCommand($this->application);
    }

    public function testHasCorrectName(): void
    {
        $this->assertSame('version', $this->command->getName());
    }

    public function testOutputsApiVersion(): void
    {
        $this->application
            ->expects($this->once())
            ->method('all')
            ->willReturn([])
        ;

        $this->pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(ApiPluginInterface::class)
            ->willReturn([])
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $expected = json_encode(
            [
                'version' => Application::VERSION,
                'commands' => [],
                'features' => [],
            ],
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame($expected, $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOutputsPluginCommands(): void
    {
        $this->application
            ->expects($this->once())
            ->method('all')
            ->willReturn(['foo:bar' => 'a-command-instance'])
        ;

        $this->pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(ApiPluginInterface::class)
            ->willReturn([])
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $expected = json_encode(
            [
                'version' => Application::VERSION,
                'commands' => ['foo:bar'],
                'features' => [],
            ],
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame($expected, $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOutputsPluginFeatures(): void
    {
        $plugin = $this->createMock(ApiPluginInterface::class);
        $plugin
            ->expects($this->once())
            ->method('getApiFeatures')
            ->willReturn([
                'foo' => 'bar',
                'bar' => 'baz',
            ])
        ;

        $this->application
            ->expects($this->once())
            ->method('all')
            ->willReturn([])
        ;

        $this->pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(ApiPluginInterface::class)
            ->willReturn(['foo/bar-bundle' => $plugin])
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $expected = json_encode(
            [
                'version' => Application::VERSION,
                'commands' => [],
                'features' => [
                    'foo/bar-bundle' => [
                        'foo' => 'bar',
                        'bar' => 'baz',
                    ],
                ],
            ],
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame($expected, $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
