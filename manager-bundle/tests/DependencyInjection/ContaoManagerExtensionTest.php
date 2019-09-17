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
use Contao\ManagerBundle\EventListener\BackendMenuListener;
use Contao\ManagerBundle\EventListener\InitializeApplicationListener;
use Contao\ManagerBundle\EventListener\InstallCommandListener;
use Contao\ManagerBundle\EventListener\PreviewAuthenticationListener;
use Contao\ManagerBundle\Routing\RouteLoader;
use Contao\ManagerBundle\Security\Logout\LogoutHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoManagerExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder();

        $extension = new ContaoManagerExtension();
        $extension->load([], $this->container);
    }

    public function testRegistersTheBackendMenuListener(): void
    {
        $this->assertTrue($this->container->has('contao_manager.listener.backend_menu_listener'));

        $definition = $this->container->getDefinition('contao_manager.listener.backend_menu_listener');

        $this->assertSame(BackendMenuListener::class, $definition->getClass());
        $this->assertSame('security.helper', (string) $definition->getArgument(0));
        $this->assertSame('%contao_manager.manager_path%', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('contao.backend_menu_build', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onBuild', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(-10, $tags['kernel.event_listener'][0]['priority']);
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

    public function testRegistersThePreviewAuthenticationListener(): void
    {
        $this->assertTrue($this->container->has('contao_manager.listener.preview_authentication'));

        $definition = $this->container->getDefinition('contao_manager.listener.preview_authentication');

        $this->assertSame(PreviewAuthenticationListener::class, $definition->getClass());
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(0));
        $this->assertSame('contao.security.token_checker', (string) $definition->getArgument(1));
        $this->assertSame('router', (string) $definition->getArgument(2));
        $this->assertSame('%contao.preview_script%', (string) $definition->getArgument(3));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(7, $tags['kernel.event_listener'][0]['priority']);
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

    public function testRegistersTheJwtManager(): void
    {
        $this->assertTrue($this->container->has('contao_manager.jwt_manager'));

        $definition = $this->container->getDefinition('contao_manager.jwt_manager');

        $this->assertTrue($definition->isSynthetic());
        $this->assertTrue($definition->isPublic());
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

    public function testRegistersTheLogoutHandler(): void
    {
        $this->assertTrue($this->container->has('contao_manager.security.logout_handler'));

        $definition = $this->container->getDefinition('contao_manager.security.logout_handler');

        $this->assertSame(LogoutHandler::class, $definition->getClass());
        $this->assertSame('contao_manager.jwt_manager', (string) $definition->getArgument(0));
    }
}
