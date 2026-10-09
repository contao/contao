<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\ContaoManager;

use Contao\ApiBundle\ContaoApiBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Contao\McpBundle\ContaoMcpBundle;
use Contao\OAuthServerBundle\ContaoOAuthServerBundle;
use Symfony\AI\McpBundle\McpBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
class Plugin implements BundlePluginInterface, ConfigPluginInterface, ExtensionPluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoMcpBundle::class)
                ->setLoadAfter([ContaoApiBundle::class, ContaoCoreBundle::class, ContaoOAuthServerBundle::class]),
            BundleConfig::create(McpBundle::class)
                ->setLoadAfter([ContaoMcpBundle::class]),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): RouteCollection|null
    {
        $path = Path::join(__DIR__, '../../config/routes.yaml');

        return $resolver->resolve($path)->load($path);
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(Path::join(__DIR__, '../../config/mcp.yaml'));
        $loader->load(Path::join(__DIR__, '../../skeleton/config/api_platform.yaml'));
    }

    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container): array
    {
        if ('security' !== $extensionName) {
            return $extensionConfigs;
        }

        // Do not add the firewall if the application already defines it
        foreach ($extensionConfigs as $config) {
            if (isset($config['firewalls']['contao_mcp'])) {
                return $extensionConfigs;
            }
        }

        foreach ($extensionConfigs as &$config) {
            $before = match (true) {
                isset($config['firewalls']['contao_backend_api']) => 'contao_backend_api',
                isset($config['firewalls']['contao_backend']) => 'contao_backend',
                default => null,
            };

            if (null === $before) {
                continue;
            }

            $firewalls = [];

            foreach ($config['firewalls'] as $name => $firewall) {
                if ($before === $name) {
                    $firewalls['contao_mcp'] = [
                        'request_matcher' => 'contao_mcp.routing.mcp_request_matcher',
                        'stateless' => true,
                        'provider' => 'contao.security.backend_user_provider',
                        'user_checker' => 'contao.security.user_checker',
                        'custom_authenticators' => ['contao_oauth_server.security.bearer_authenticator'],
                        'entry_point' => 'contao_oauth_server.security.bearer_authenticator',
                    ];
                }

                $firewalls[$name] = $firewall;
            }

            $config['firewalls'] = $firewalls;

            // Add the firewall only once, otherwise the configs would be merged and list
            // options like "custom_authenticators" would contain duplicates
            break;
        }

        return $extensionConfigs;
    }
}
