<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\McpBundle\ContaoManager\Plugin;
use Contao\ApiBundle\ContaoApiBundle;
use Contao\McpBundle\ContaoMcpBundle;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

final class PluginTest extends TestCase
{
    public function testRegistersTheMcpBundleInTheCorrectOrder(): void
    {
        $plugin = new Plugin();
        $bundles = $plugin->getBundles($this->createStub(ParserInterface::class));

        $this->assertCount(2, $bundles);
        $this->assertSame(ContaoMcpBundle::class, $bundles[0]->getName());
        $this->assertSame([ContaoApiBundle::class, ContaoCoreBundle::class], $bundles[0]->getLoadAfter());
        $this->assertSame(McpBundle::class, $bundles[1]->getName());
        $this->assertSame([ContaoMcpBundle::class], $bundles[1]->getLoadAfter());
    }

    public function testLoadsTheSkeletonConfigAndMcpRoutes(): void
    {
        $plugin = new Plugin();
        $resolver = $this->createMock(LoaderResolverInterface::class);
        $loader = $this->createMock(LoaderInterface::class);
        $routeCollection = new RouteCollection();

        $resolver
            ->expects($this->once())
            ->method('resolve')
            ->with(\dirname(__DIR__, 2).'/src/ContaoManager/../../config/routes.yaml')
            ->willReturn($loader)
        ;

        $loader
            ->expects($this->once())
            ->method('load')
            ->with(\dirname(__DIR__, 2).'/src/ContaoManager/../../config/routes.yaml')
            ->willReturn($routeCollection)
        ;

        $this->assertSame($routeCollection, $plugin->getRouteCollection($resolver, $this->createStub(KernelInterface::class)));

        $loader = $this->createMock(LoaderInterface::class);
        $paths = [];
        $loader
            ->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(
                static function (string $path) use (&$paths): void {
                    $paths[] = $path;
                },
            )
        ;

        $plugin->registerContainerConfiguration($loader, []);

        $this->assertSame(
            [
                \dirname(__DIR__, 2).'/src/ContaoManager/../../skeleton/config/mcp.yaml',
                \dirname(__DIR__, 2).'/src/ContaoManager/../../skeleton/config/api_platform.yaml',
            ],
            $paths,
        );
    }

    public function testProtectsTheMcpRouteWithBackendScope(): void
    {
        $route = Yaml::parseFile(\dirname(__DIR__, 2).'/src/ContaoManager/../../config/routes.yaml')['mcp'];

        $this->assertSame(['_scope' => 'backend'], $route['defaults']);
    }
}
