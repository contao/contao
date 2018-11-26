<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\DependencyInjection;

use Contao\ManagerBundle\Cache\BundleCacheClearer;
use Contao\ManagerBundle\DependencyInjection\ContaoManagerExtension;
use Contao\ManagerBundle\EventListener\InitializeApplicationListener;
use Contao\ManagerBundle\EventListener\InstallCommandListener;
use Contao\ManagerBundle\Routing\RouteLoader;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class ContaoManagerExtensionTest extends ContaoTestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder();
        $this->container->setParameter('contao.web_dir', $this->getTempDir().'/web');

        $extension = new ContaoManagerExtension();
        $extension->load([], $this->container);
    }

    public function testRegistersTheInitializeApplicationListener(): void
    {
        $this->assertTrue($this->container->has('contao_manager.listener.initialize_application'));

        $definition = $this->container->getDefinition('contao_manager.listener.initialize_application');

        $this->assertSame(InitializeApplicationListener::class, $definition->getClass());
        $this->assertSame('%kernel.project_dir%', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('contao_installation.initialize_application', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onInitializeApplication', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(-128, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheInstallCommandListener(): void
    {
        $this->assertTrue($this->container->has('contao_manager.listener.install_command'));

        $definition = $this->container->getDefinition('contao_manager.listener.install_command');

        $this->assertSame(InstallCommandListener::class, $definition->getClass());
        $this->assertSame('%kernel.project_dir%', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('console.terminate', $tags['kernel.event_listener'][0]['event']);
    }

    public function testRegistersTheBundleCacheClearer(): void
    {
        $this->assertTrue($this->container->has('contao_manager.cache.clear_bundle'));

        $definition = $this->container->getDefinition('contao_manager.cache.clear_bundle');

        $this->assertSame(BundleCacheClearer::class, $definition->getClass());
        $this->assertSame('filesystem', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.cache_clearer', $tags);
    }

    public function testRegistersThePluginLoader(): void
    {
        $this->assertTrue($this->container->has('contao_manager.plugin_loader'));

        $definition = $this->container->getDefinition('contao_manager.plugin_loader');

        $this->assertTrue($definition->isSynthetic());
        $this->assertTrue($definition->isPublic());
    }

    public function testRegistersTheRoutingLoader(): void
    {
        $this->assertTrue($this->container->has('contao_manager.routing_loader'));

        $definition = $this->container->getDefinition('contao_manager.routing_loader');

        $this->assertSame(RouteLoader::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('routing.loader', (string) $definition->getArgument(0));
        $this->assertSame('contao_manager.plugin_loader', (string) $definition->getArgument(1));
        $this->assertSame('kernel', (string) $definition->getArgument(2));
        $this->assertSame('%kernel.project_dir%', (string) $definition->getArgument(3));
    }

    /**
     * @dataProvider getManagerPaths
     */
    public function testRegistersTheContaoManagerPath(string $file, array $config): void
    {
        $webDir = $this->container->getParameter('contao.web_dir');

        $fs = new Filesystem();
        $fs->dumpFile($webDir.'/'.$file, '');

        $extension = new ContaoManagerExtension();
        $extension->load([$config], $this->container);

        $this->assertFileExists($webDir.'/'.$file);
        $this->assertSame($file, $this->container->getParameter('contao_manager.manager_path'));

        $fs->remove($webDir.'/'.$file);
    }

    /**
     * @return array<int,array<int,array<string,string>|string>>
     */
    public function getManagerPaths(): array
    {
        return [
            ['contao-manager.phar.php', []],
            ['custom.phar.php', ['manager_path' => 'custom.phar.php']],
        ];
    }
}
