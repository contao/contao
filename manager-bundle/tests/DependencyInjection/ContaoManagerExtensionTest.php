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
use Contao\ManagerBundle\Controller\DebugController;
use Contao\ManagerBundle\DependencyInjection\ContaoManagerExtension;
use Contao\ManagerBundle\EventListener\BackendMenuListener;
use Contao\ManagerBundle\EventListener\InitializeApplicationListener;
use Contao\ManagerBundle\EventListener\InstallCommandListener;
use Contao\ManagerBundle\Routing\RouteLoader;
use Contao\ManagerBundle\Security\Logout\LogoutHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

class ContaoManagerExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

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
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.helper'),
                new Reference('router'),
                new Reference('request_stack'),
                new Reference('translator'),
                new Reference('%kernel.debug%'),
                new Reference('%contao_manager.manager_path%'),
                new Reference('contao_manager.jwt_manager', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheInitializeApplicationListener(): void
    {
        $this->assertTrue($this->container->has('contao_manager.listener.initialize_application'));

        $definition = $this->container->getDefinition('contao_manager.listener.initialize_application');

        $this->assertSame(InitializeApplicationListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('%kernel.project_dir%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => -128,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheInstallCommandListener(): void
    {
        $this->assertTrue($this->container->has('contao_manager.listener.install_command'));

        $definition = $this->container->getDefinition('contao_manager.listener.install_command');

        $this->assertSame(InstallCommandListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('%kernel.project_dir%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheBundleCacheClearer(): void
    {
        $this->assertTrue($this->container->has('contao_manager.cache.clear_bundle'));

        $definition = $this->container->getDefinition('contao_manager.cache.clear_bundle');

        $this->assertSame(BundleCacheClearer::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('filesystem', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheDebugController(): void
    {
        $this->assertTrue($this->container->has(DebugController::class));

        $definition = $this->container->getDefinition(DebugController::class);

        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('security.helper'),
                new Reference('request_stack'),
                new Reference('contao_manager.jwt_manager'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheJwtManager(): void
    {
        $this->assertTrue($this->container->has('contao_manager.jwt_manager'));

        $definition = $this->container->getDefinition('contao_manager.jwt_manager');

        $this->assertTrue($definition->isPublic());
        $this->assertTrue($definition->isSynthetic());
    }

    public function testRegistersThePluginLoader(): void
    {
        $this->assertTrue($this->container->has('contao_manager.plugin_loader'));

        $definition = $this->container->getDefinition('contao_manager.plugin_loader');

        $this->assertTrue($definition->isPublic());
        $this->assertTrue($definition->isSynthetic());
    }

    public function testRegistersTheRoutingLoader(): void
    {
        $this->assertTrue($this->container->has('contao_manager.routing_loader'));

        $definition = $this->container->getDefinition('contao_manager.routing_loader');

        $this->assertSame(RouteLoader::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('routing.loader'),
                new Reference('contao_manager.plugin_loader'),
                new Reference('kernel'),
                new Reference('%kernel.project_dir%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheLogoutHandler(): void
    {
        $this->assertTrue($this->container->has('contao_manager.security.logout_handler'));

        $definition = $this->container->getDefinition('contao_manager.security.logout_handler');

        $this->assertSame(LogoutHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao_manager.jwt_manager', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }
}
