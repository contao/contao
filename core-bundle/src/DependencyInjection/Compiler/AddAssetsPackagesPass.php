<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AddAssetsPackagesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('assets.packages')) {
            return;
        }

        $this->addBundles($container);
        $this->addComponents($container);
    }

    /**
     * Adds every bundle with a public folder as assets package.
     *
     * @param ContainerBuilder $container
     */
    private function addBundles(ContainerBuilder $container): void
    {
        $packages = $container->getDefinition('assets.packages');
        $context = new Reference('contao.assets.plugins_context');

        if ($container->hasDefinition('assets._version_default')) {
            $version = new Reference('assets._version_default');
        } else {
            $version = new Reference('assets.empty_version_strategy');
        }

        /** @var Bundle[] $bundles */
        $bundles = $container->get('kernel')->getBundles();

        foreach ($bundles as $bundle) {
            if (!is_dir($originDir = $bundle->getPath().'/Resources/public')) {
                continue;
            }

            if ($extension = $bundle->getContainerExtension()) {
                $packageName = $extension->getAlias();
            } else {
                $packageName = $this->getBundlePackageName($bundle);
            }

            $serviceId = 'assets._package_'.$packageName;
            $basePath = 'bundles/'.preg_replace('/bundle$/', '', strtolower($bundle->getName()));

            $container->setDefinition($serviceId, $this->createPackageDefinition($basePath, $version, $context));
            $packages->addMethodCall('addPackage', [$packageName, new Reference($serviceId)]);
        }
    }

    /**
     * Adds the Contao components as assets packages.
     *
     * @param ContainerBuilder $container
     */
    private function addComponents(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kernel.packages')) {
            return;
        }

        $packages = $container->getDefinition('assets.packages');
        $context = new Reference('contao.assets.plugins_context');
        $components = $container->getParameter('kernel.packages');

        foreach ($components as $name => $version) {
            [$vendor, $packageName] = explode('/', $name, 2);

            if ('contao-components' !== $vendor) {
                continue;
            }

            $serviceId = 'assets._package_'.$name;
            $basePath = 'assets/'.$packageName;
            $version = $this->createPackageVersion($container, $version, $name);

            $container->setDefinition($serviceId, $this->createPackageDefinition($basePath, $version, $context));
            $packages->addMethodCall('addPackage', [$name, new Reference($serviceId)]);
        }
    }

    /**
     * Creates an assets package definition.
     *
     * @param string    $basePath
     * @param Reference $version
     * @param Reference $context
     *
     * @return Definition
     */
    private function createPackageDefinition(string $basePath, Reference $version, Reference $context): Definition
    {
        $package = new ChildDefinition('assets.path_package');

        $package
            ->setPublic(false)
            ->replaceArgument(0, $basePath)
            ->replaceArgument(1, $version)
            ->replaceArgument(2, $context)
        ;

        return $package;
    }

    /**
     * Creates an asset package version strategy.
     *
     * @param ContainerBuilder $container
     * @param string           $version
     * @param string           $name
     *
     * @return Reference
     */
    private function createPackageVersion(ContainerBuilder $container, string $version, string $name): Reference
    {
        $def = new ChildDefinition('assets.static_version_strategy');
        $def->replaceArgument(0, $version);

        $container->setDefinition('assets._version_'.$name, $def);

        return new Reference('assets._version_'.$name);
    }

    /**
     * Returns a bundle package name emulating what a bundle extension would look like.
     *
     * @param Bundle $bundle
     *
     * @return string
     */
    private function getBundlePackageName(Bundle $bundle): string
    {
        $className = $bundle->getName();

        if ('Bundle' === substr($className, -6)) {
            $className = substr($className, 0, -6);
        }

        return Container::underscore($className);
    }
}
