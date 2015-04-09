<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Adds the bundle resources paths to the container.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AddResourcesPathsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter('contao.resources_paths', $this->getResourcesPath($container));
    }

    /**
     * Returns the Contao resources paths as array.
     *
     * @param ContainerBuilder $container The container object
     *
     * @return array The resources paths
     */
    private function getResourcesPath(ContainerBuilder $container)
    {
        $paths   = [];
        $rootDir = dirname($container->getParameter('kernel.root_dir'));

        foreach ($container->getParameter('kernel.bundles') as $name => $class) {
            $reflection = new \ReflectionClass($class);

            if ('ContaoModuleBundle' === $reflection->getShortName()) {
                $paths[] = "$rootDir/system/modules/$name";
            } elseif (is_dir($dir = dirname($reflection->getFilename()) . '/Resources/contao')) {
                $paths[] = $dir;
            }
        }

        return $paths;
    }
}
