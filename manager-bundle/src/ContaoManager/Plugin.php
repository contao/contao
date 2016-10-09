<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager;

use Contao\ManagerBundle\Manager\Bundle\BundlePluginInterface;
use Contao\ManagerBundle\Manager\Bundle\IniParser;
use Contao\ManagerBundle\Manager\Bundle\JsonParser;
use Contao\ManagerBundle\ContaoManager\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Plugin for the Contao Manager.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * @inheritdoc
     */
    public function getAutoloadConfigs(JsonParser $jsonParser, IniParser $iniParser)
    {
        return $jsonParser->parse(__DIR__ . '/../Resources/contao-manager/bundles.json');
    }

    /**
     * @inheritdoc
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        if ('dev' !== $kernel->getEnvironment()) {
            return null;
        }

        $collections = [];
        $files = [
            '_wdt' => '@WebProfilerBundle/Resources/config/routing/wdt.xml',
            '_profiler' => '@WebProfilerBundle/Resources/config/routing/profiler.xml'
        ];

        foreach ($files as $prefix => $file) {
            /** @var RouteCollection $collection */
            $collection = $resolver->resolve($file)->load($file);
            $collection->addPrefix($prefix);

            $collections[] = $collection;
        }

        return array_reduce(
            $collections,
            function (RouteCollection $carry, RouteCollection $item) {
                $carry->addCollection($item);

                return $carry;
            },
            new RouteCollection()
        );
    }
}
