<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Fixtures\HttpKernel;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate;

class CacheWarmerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->register('contao_manager.plugin_loader')
            ->setPublic(true)
            ->setSynthetic(true)
        ;
        $container
            ->register('contao_manager.test.plugin_loader_warmer', PluginLoaderCacheWarmer::class)
            ->addArgument(new Reference('contao_manager.plugin_loader'))
            ->addTag('kernel.cache_warmer')
        ;
        $container
            ->register('cache_warmer', CacheWarmerAggregate::class)
            ->setPublic(true)
            ->addArgument(new TaggedIteratorArgument('kernel.cache_warmer'))
        ;
    }
}
