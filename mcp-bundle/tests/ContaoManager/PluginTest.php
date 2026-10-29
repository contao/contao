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

use Contao\ApiBundle\ContaoApiBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\McpBundle\ContaoManager\Plugin;
use Contao\McpBundle\ContaoMcpBundle;
use Contao\OAuthServerBundle\ContaoOAuthServerBundle;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Filesystem\Path;
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
        $this->assertSame([ContaoApiBundle::class, ContaoCoreBundle::class, ContaoOAuthServerBundle::class], $bundles[0]->getLoadAfter());
        $this->assertSame(McpBundle::class, $bundles[1]->getName());
        $this->assertSame([ContaoMcpBundle::class], $bundles[1]->getLoadAfter());
    }

    public function testLoadsTheBundleConfigAndMcpRoutes(): void
    {
        $plugin = new Plugin();
        $routeCollection = new RouteCollection();

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with(Path::join(\dirname(__DIR__, 2), 'src/ContaoManager/../../config/routes.yaml'))
            ->willReturn($routeCollection)
        ;

        $resolver = $this->createMock(LoaderResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolve')
            ->with(Path::join(\dirname(__DIR__, 2), 'src/ContaoManager/../../config/routes.yaml'))
            ->willReturn($loader)
        ;

        $this->assertSame($routeCollection, $plugin->getRouteCollection($resolver, $this->createStub(KernelInterface::class)));

        $paths = [];

        $loader = $this->createMock(LoaderInterface::class);
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
                Path::join(\dirname(__DIR__, 2), 'src/ContaoManager/../../config/mcp.yaml'),
                Path::join(\dirname(__DIR__, 2), 'src/ContaoManager/../../skeleton/config/api_platform.yaml'),
            ],
            $paths,
        );
    }

    public function testDisablesAutomaticApiToolRegistration(): void
    {
        $config = Yaml::parseFile(\dirname(__DIR__, 2).'/skeleton/config/api_platform.yaml');

        $this->assertFalse($config['api_platform']['mcp']['enabled']);
    }

    public function testRoutesToTheBackendController(): void
    {
        $route = Yaml::parseFile(\dirname(__DIR__, 2).'/src/ContaoManager/../../config/routes.yaml')['contao_mcp_backend'];

        $config = Yaml::parseFile(\dirname(__DIR__, 2).'/config/mcp.yaml')['mcp'];

        $this->assertSame($route['path'], $config['servers']['contao_backend']['http']['path']);
        $this->assertSame(['_scope' => 'backend', '_stateless' => true], $route['defaults']);
        $this->assertSame('%contao.backend.route_prefix%/mcp', $route['path']);

        $this->assertSame('mcp.server.contao_backend.controller::handle', $route['controller']);
        $this->assertSame(['GET', 'POST', 'DELETE', 'OPTIONS'], $route['methods']);
        $this->assertArrayNotHasKey('resource', $route);
    }

    public function testAddsTheMcpFirewallBeforeTheBackendApiFirewall(): void
    {
        $extensionConfigs = [
            [
                'firewalls' => [
                    'dev' => ['security' => false],
                    'contao_backend_api' => ['stateless' => true],
                    'contao_backend' => [],
                ],
            ],
        ];

        $plugin = new Plugin();
        $config = $plugin->getExtensionConfig('security', $extensionConfigs, $this->createStub(ContainerBuilder::class));

        $this->assertSame(['dev', 'contao_mcp', 'contao_backend_api', 'contao_backend'], array_keys($config[0]['firewalls']));

        $this->assertSame(
            [
                'request_matcher' => 'contao_mcp.routing.mcp_request_matcher',
                'stateless' => true,
                'provider' => 'contao.security.backend_user_provider',
                'user_checker' => 'contao.security.user_checker',
                'custom_authenticators' => ['contao_oauth_server.security.bearer_authenticator'],
                'entry_point' => 'contao_oauth_server.security.bearer_authenticator',
            ],
            $config[0]['firewalls']['contao_mcp'],
        );
    }

    public function testDoesNotOverrideAnExistingMcpFirewall(): void
    {
        $extensionConfigs = [
            ['firewalls' => ['contao_mcp' => ['custom' => true], 'contao_backend' => []]],
        ];

        $plugin = new Plugin();

        $this->assertSame($extensionConfigs, $plugin->getExtensionConfig('security', $extensionConfigs, $this->createStub(ContainerBuilder::class)));
        $this->assertSame([['foo' => 'bar']], $plugin->getExtensionConfig('framework', [['foo' => 'bar']], $this->createStub(ContainerBuilder::class)));
    }

    public function testDoesNotAddTheMcpFirewallIfALaterConfigDefinesIt(): void
    {
        $extensionConfigs = [
            ['firewalls' => ['contao_backend_api' => [], 'contao_backend' => []]],
            ['firewalls' => ['contao_mcp' => ['custom' => true]]],
        ];

        $plugin = new Plugin();

        $this->assertSame($extensionConfigs, $plugin->getExtensionConfig('security', $extensionConfigs, $this->createStub(ContainerBuilder::class)));
    }

    public function testAddsTheMcpFirewallOnlyOnce(): void
    {
        $extensionConfigs = [
            ['firewalls' => ['contao_backend_api' => [], 'contao_backend' => []]],
            ['firewalls' => ['contao_backend_api' => [], 'contao_backend' => []]],
        ];

        $plugin = new Plugin();
        $config = $plugin->getExtensionConfig('security', $extensionConfigs, $this->createStub(ContainerBuilder::class));

        $this->assertSame(['contao_mcp', 'contao_backend_api', 'contao_backend'], array_keys($config[0]['firewalls']));
        $this->assertSame(['contao_backend_api', 'contao_backend'], array_keys($config[1]['firewalls']));
    }
}
