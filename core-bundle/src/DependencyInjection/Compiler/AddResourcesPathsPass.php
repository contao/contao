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
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
class AddResourcesPathsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->setParameter('contao.resources_paths', $this->getResourcesPaths($container));
    }

    /**
     * @return array<string>
     */
    private function getResourcesPaths(ContainerBuilder $container): array
    {
        $paths = [];
        $projectDir = $container->getParameter('kernel.project_dir');

        $bundles = $container->getParameter('kernel.bundles');
        $meta = $container->getParameter('kernel.bundles_metadata');

        foreach ($bundles as $name => $class) {
            if (ContaoModuleBundle::class === $class) {
                $paths[$name] = $meta[$name]['path'];
            } elseif (is_dir($path = Path::join($meta[$name]['path'], 'Resources/contao'))) {
                $paths[$name] = $path;
            } elseif (is_dir($path = Path::join($meta[$name]['path'], 'contao'))) {
                $paths[$name] = $path;
            }
        }

        if (is_dir($path = Path::join($projectDir, 'contao'))) {
            $paths['App'] = $path;
        }

        return $paths;
    }
}
