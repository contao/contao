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
        $paths = [];

        foreach ($this->getBundleObjects($container) as $bundle) {
            if (is_dir($path = $this->getResourcesPath($bundle))) {
                $paths[] = $path;
            }
        }

        $container->setParameter('kernel.resources_paths', $paths);
    }

    /**
     * Return the bundle objects from a list of bundle class names.
     *
     * @param ContainerBuilder $container The container object
     *
     * @return BundleInterface[] The bundle objects
     */
    private function getBundleObjects(ContainerBuilder $container)
    {
        $bundles = [];

        foreach ($container->getParameter('kernel.bundles') as $name => $class) {
            $bundles[] = new $class($name, $container->getParameter('kernel.root_dir'));
        }

        return $bundles;
    }

    /**
     * Returns the Contao resources path of a bundle.
     *
     * @param BundleInterface $bundle The bundle object
     *
     * @return string The resources path
     */
    private function getResourcesPath(BundleInterface $bundle)
    {
        if ($bundle instanceof ContaoModuleBundle) {
            return $bundle->getPath();
        }

        return $bundle->getPath() . '/Resources/contao';
    }
}
