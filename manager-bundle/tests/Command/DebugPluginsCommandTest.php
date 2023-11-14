<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Command;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\ContaoManager\Plugin as CoreBundlePlugin;
use Contao\ManagerBundle\Command\DebugPluginsCommand;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerBundle\Tests\Fixtures\ContaoManager\Plugin as FixturesPlugin;
use Contao\ManagerPlugin\PluginLoader;
use Contao\NewsBundle\ContaoNewsBundle;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class DebugPluginsCommandTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Table::class, Terminal::class]);

        parent::tearDown();
    }

    public function testNameAndArguments(): void
    {
        $command = new DebugPluginsCommand($this->getKernel([]));

        $this->assertSame('debug:plugins', $command->getName());
        $this->assertTrue($command->getDefinition()->hasArgument('name'));
        $this->assertTrue($command->getDefinition()->hasOption('bundles'));
    }

    /**
     * @dataProvider commandOutputProvider
     */
    public function testCommandOutput(array $plugins, array $bundles, array $arguments, string $expectedOutput): void
    {
        $command = new DebugPluginsCommand($this->getKernel($plugins, $bundles));

        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments);

        $this->assertStringContainsString($expectedOutput, $commandTester->getDisplay(true));
    }

    public function commandOutputProvider(): \Generator
    {
        yield 'Lists the test plugin' => [
            ['foo/bar-bundle' => new FixturesPlugin()],
            [],
            [],
            'Contao Manager Plugins',
        ];

        yield 'Lists the registered bundles by package name' => [
            ['contao/core-bundle' => new CoreBundlePlugin()],
            [],
            ['name' => 'contao/core-bundle', '--bundles' => true],
            'Bundles Registered by Plugin "Contao\CoreBundle\ContaoManager\Plugin"',
        ];

        yield 'Lists the registered bundles by class name' => [
            ['contao/core-bundle' => new CoreBundlePlugin()],
            [],
            ['name' => CoreBundlePlugin::class, '--bundles' => true],
            'Bundles Registered by Plugin "Contao\CoreBundle\ContaoManager\Plugin"',
        ];

        yield 'Lists the bundles in loading order' => [
            [],
            [
                new ContaoCoreBundle(),
                new ContaoNewsBundle(),
            ],
            ['--bundles' => true],
            'Registered Bundles in Loading Order',
        ];
    }

    public function testCannotDescribePluginBundlesIfInterfaceIsNotImplemented(): void
    {
        $command = new DebugPluginsCommand($this->getKernel(['foo/bar-bundle' => new FixturesPlugin()]));

        $commandTester = new CommandTester($command);
        $result = $commandTester->execute(['name' => 'foo/bar-bundle', '--bundles' => true]);

        $this->assertSame(-1, $result);

        $this->assertSame(
            '[ERROR] The "Contao\ManagerBundle\Tests\Fixtures\ContaoManager\Plugin" plugin does not implement the "Contao\ManagerPlugin\Bundle\BundlePluginInterface" interface.',
            $this->normalizeDisplay($commandTester->getDisplay(true)),
        );
    }

    public function testGeneratesAnErrorIfAPluginDoesNotExist(): void
    {
        $command = new DebugPluginsCommand($this->getKernel(['foo/bar-bundle' => new FixturesPlugin()]));

        $commandTester = new CommandTester($command);
        $result = $commandTester->execute(['name' => 'foo/baz-bundle', '--bundles' => true]);

        $this->assertSame(-1, $result);

        $this->assertSame(
            '[ERROR] No plugin with the class or package name "foo/baz-bundle" found.',
            $this->normalizeDisplay($commandTester->getDisplay(true)),
        );
    }

    private function getKernel(array $plugins, array $bundles = []): ContaoKernel
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getTempDir());
        $container->set('filesystem', new Filesystem());

        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects($plugins ? $this->once() : $this->never())
            ->method('getInstances')
            ->willReturn($plugins)
        ;

        $kernel = $this->createMock(ContaoKernel::class);
        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        $kernel
            ->method('getPluginLoader')
            ->willReturn($pluginLoader)
        ;

        $kernel
            ->method('getBundles')
            ->willReturn($bundles)
        ;

        $kernel
            ->method('getProjectDir')
            ->willReturn(\dirname(__DIR__, 4))
        ;

        $container->set('kernel', $kernel);

        return $kernel;
    }

    private function normalizeDisplay(string $string): string
    {
        return trim(preg_replace('/[ \n]+/', ' ', $string));
    }
}
