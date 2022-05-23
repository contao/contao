<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class AddEntityExtensionPathsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->setParameter('contao.entity_extension_paths', $this->getEntityExtensionPaths($container));
    }

    /**
     * @return array<string>
     */
    private function getEntityExtensionPaths(ContainerBuilder $container): array
    {
        $paths = [];
        $rootDir = $container->getParameter('kernel.project_dir');

        $bundles = $container->getParameter('kernel.bundles');
        $meta = $container->getParameter('kernel.bundles_metadata');

        foreach ($bundles as $name => $class) {
            if (ContaoModuleBundle::class === $class) {
                $paths[] = $meta[$name]['path'];
            } elseif (is_dir($path = $meta[$name]['path'].'/EntityExtension')) {
                $paths[] = $path;
            }
        }

        if (is_dir($rootDir.'/src/EntityExtension')) {
            $paths[] = $rootDir.'/src/EntityExtension';
        }

        return $paths;
    }
}
