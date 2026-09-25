<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ContaoManager;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Contao\ApiBundle\ContaoApiBundle;
use Contao\ApiBundle\ContaoManager\Plugin;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

final class PluginTest extends TestCase
{
    public function testRegistersTheApiPlatformAndApiBundlesInTheCorrectOrder(): void
    {
        $plugin = new Plugin();
        $bundles = $plugin->getBundles($this->createStub(ParserInterface::class));

        $this->assertCount(2, $bundles);
        $this->assertSame(ApiPlatformBundle::class, $bundles[0]->getName());
        $this->assertSame([], $bundles[0]->getLoadAfter());
        $this->assertSame(ContaoApiBundle::class, $bundles[1]->getName());
        $this->assertSame([ApiPlatformBundle::class, ContaoCoreBundle::class], $bundles[1]->getLoadAfter());
    }

    public function testLoadsApiPlatformRoutes(): void
    {
        $plugin = new Plugin();
        $routesPath = \dirname(__DIR__, 2).\DIRECTORY_SEPARATOR.'src'.\DIRECTORY_SEPARATOR.'ContaoManager/../../config/routes.yaml';
        $routeCollection = new RouteCollection();

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($routesPath)
            ->willReturn($routeCollection)
        ;

        $resolver = $this->createMock(LoaderResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolve')
            ->with($routesPath)
            ->willReturn($loader)
        ;

        $this->assertSame($routeCollection, $plugin->getRouteCollection($resolver, $this->createStub(KernelInterface::class)));
    }

    public function testAllApiRoutesUseTheBackendScope(): void
    {
        $route = Yaml::parseFile(\dirname(__DIR__, 2).'/config/routes.yaml')['api_platform'];

        $this->assertSame('%contao.backend.route_prefix%/_api', $route['prefix']);
        $this->assertSame(['_scope' => 'backend'], $route['defaults']);
    }

    public function testLoadsApiPlatformDefaults(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with(\dirname(__DIR__, 2).'/src/ContaoManager/../../config/config.yaml')
        ;

        new Plugin()->registerContainerConfiguration($loader, []);
    }
}
