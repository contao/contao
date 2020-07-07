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

use Contao\CalendarBundle\ContaoManager\Plugin as CalendarBundlePlugin;
use Contao\CommentsBundle\ContaoManager\Plugin as CommentsBundlePlugin;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\ContaoManager\Plugin as CoreBundlePlugin;
use Contao\FaqBundle\ContaoManager\Plugin as FaqBundlePlugin;
use Contao\InstallationBundle\ContaoManager\Plugin as InstallationBundlePlugin;
use Contao\ListingBundle\ContaoManager\Plugin as ListingBundlePlugin;
use Contao\ManagerBundle\Command\DebugPluginsCommand;
use Contao\ManagerBundle\ContaoManager\Plugin as ManagerBundlePlugin;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerBundle\Tests\Fixtures\ContaoManager\Plugin as FixturesPlugin;
use Contao\ManagerPlugin\PluginLoader;
use Contao\NewsBundle\ContaoManager\Plugin as NewsBundlePlugin;
use Contao\NewsBundle\ContaoNewsBundle;
use Contao\NewsletterBundle\ContaoManager\Plugin as NewsletterBundlePlugin;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class DebugPluginsCommandTest extends ContaoTestCase
{
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

        $this->assertSame($expectedOutput, $commandTester->getDisplay(true));
    }

    public function commandOutputProvider(): \Generator
    {
        yield 'Lists the plugins' => [
            [
                'contao/core-bundle' => new CoreBundlePlugin(),
                'contao/calendar-bundle' => new CalendarBundlePlugin(),
                'contao/comments-bundle' => new CommentsBundlePlugin(),
                'contao/faq-bundle' => new FaqBundlePlugin(),
                'contao/installation-bundle' => new InstallationBundlePlugin(),
                'contao/listing-bundle' => new ListingBundlePlugin(),
                'contao/news-bundle' => new NewsBundlePlugin(),
                'contao/newsletter-bundle' => new NewsletterBundlePlugin(),
                'contao/manager-bundle' => new ManagerBundlePlugin(),
            ],
            [],
            [],
            $this->getOutput('plugins'),
        ];

        yield 'Lists the test plugin' => [
            ['foo/bar-bundle' => new FixturesPlugin()],
            [],
            [],
            $this->getOutput('test_plugin'),
        ];

        yield 'Lists the registered bundles by package name' => [
            ['contao/core-bundle' => new CoreBundlePlugin()],
            [],
            ['name' => 'contao/core-bundle', '--bundles' => true],
            $this->getOutput('registered_bundles'),
        ];

        yield 'Lists the registered bundles by class name' => [
            ['contao/core-bundle' => new CoreBundlePlugin()],
            [],
            ['name' => CoreBundlePlugin::class, '--bundles' => true],
            $this->getOutput('registered_bundles'),
        ];

        yield 'Lists the bundles in loading order' => [
            [],
            [
                new ContaoCoreBundle(),
                new ContaoNewsBundle(),
            ],
            ['--bundles' => true],
            $this->getOutput('loading_order'),
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
            $this->normalizeDisplay($commandTester->getDisplay(true))
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
            $this->normalizeDisplay($commandTester->getDisplay(true))
        );
    }

    private function getKernel(array $plugins, array $bundles = []): ContaoKernel
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getTempDir());
        $container->set('filesystem', new Filesystem());

        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects(0 === \count($plugins) ? $this->never() : $this->once())
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

    private function getOutput(string $outputFile): string
    {
        $output = file_get_contents(Path::join(__DIR__, '../Fixtures/output', "$outputFile.out"));

        // Replace check mark with '1' on Windows
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $output = str_replace('✔', '1', $output);
        }

        return $output;
    }

    private function normalizeDisplay(string $string): string
    {
        return trim(preg_replace('/[ \n]+/', ' ', $string));
    }
}
