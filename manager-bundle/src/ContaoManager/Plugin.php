<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager;

use Contao\ManagerBundle\ContaoManager\Bundle\ParserInterface;
use Contao\ManagerBundle\ContaoManager\Config\ConfigPluginInterface;
use Contao\ManagerBundle\ContaoManager\Routing\RoutingPluginInterface;
use Contao\ManagerBundle\ContaoManager\Bundle\BundlePluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Plugin for the Contao Manager.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class Plugin implements BundlePluginInterface, ConfigPluginInterface, RoutingPluginInterface
{
    /**
     * @inheritdoc
     */
    public function getBundles(ParserInterface $parser)
    {
        return $parser->parse(__DIR__ . '/../Resources/contao-manager/bundles.json');
    }

    /**
     * @inheritdoc
     */
    public function prependConfig(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('framework.yml');
        $loader->load('twig.yml');
        $loader->load('doctrine.yml');
        $loader->load('swiftmailer.yml');
        $loader->load('monolog.yml');

        if (in_array('Lexik\\Bundle\\MaintenanceBundle\\LexikMaintenanceBundle', $container->getParameter('kernel.bundles'), true)) {
            $loader->load('lexik_maintenance.yml');
        }

        if ('dev' === $container->getParameter('kernel.environment')) {
            $loader->load('web_profiler.yml');
        }

        $coreLoader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../../core-bundle/src/Resources/config'));
        $coreLoader->load('security.yml');
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
