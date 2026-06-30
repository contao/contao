<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ContaoManager;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Contao\ApiBundle\ContaoApiBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
class Plugin implements BundlePluginInterface, ConfigPluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ApiPlatformBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
            BundleConfig::create(ContaoApiBundle::class)
                ->setLoadAfter([ApiPlatformBundle::class]),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): RouteCollection|null
    {
        $path = __DIR__.'/../../config/routes.yaml';

        return $resolver->resolve($path)->load($path);
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(__DIR__.'/../../skeleton/config/config.yaml');
        $loader->load(__DIR__.'/../../skeleton/config/services.yaml');
    }
}
