<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
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
     * @var string|null
     */
    private static $autoloadModules = null;

    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        $configs = $parser->parse(__DIR__.'/../Resources/contao-manager/bundles.json');

        if (null !== static::$autoloadModules && file_exists(static::$autoloadModules)) {
            /** @var Finder $modules */
            $modules = (new Finder())
                ->directories()
                ->depth(0)
                ->in(static::$autoloadModules)
            ;

            foreach ($modules as $module) {
                if (file_exists($module->getPathname().'/.skip')) {
                    continue;
                }

                $configs = array_merge($configs, $parser->parse($module->getFilename(), 'ini'));
            }
        }

        return $configs;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig)
    {
        $loader->load('@ContaoManagerBundle/Resources/contao-manager/framework.yml');
        $loader->load('@ContaoManagerBundle/Resources/contao-manager/security.yml');
        $loader->load('@ContaoManagerBundle/Resources/contao-manager/contao.yml');
        $loader->load('@ContaoManagerBundle/Resources/contao-manager/twig.yml');
        $loader->load('@ContaoManagerBundle/Resources/contao-manager/doctrine.yml');
        $loader->load('@ContaoManagerBundle/Resources/contao-manager/swiftmailer.yml');
        $loader->load('@ContaoManagerBundle/Resources/contao-manager/monolog.yml');
        $loader->load('@ContaoManagerBundle/Resources/contao-manager/lexik_maintenance.yml');
        $loader->load('@ContaoManagerBundle/Resources/contao-manager/nelmio_cors.yml');

        $loader->load(function (ContainerBuilder $container) use ($loader) {
            if ('dev' === $container->getParameter('kernel.environment')) {
                $loader->load('@ContaoManagerBundle/Resources/contao-manager/web_profiler.yml');
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        if ('dev' !== $kernel->getEnvironment()) {
            return null;
        }

        $collections = [];

        $files = [
            '_wdt' => '@WebProfilerBundle/Resources/config/routing/wdt.xml',
            '_profiler' => '@WebProfilerBundle/Resources/config/routing/profiler.xml',
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

    /**
     * Sets path to enable autoloading of legacy Contao modules.
     *
     * @param string $modulePath
     */
    public static function autoloadModules($modulePath)
    {
        static::$autoloadModules = $modulePath;
    }
}
