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
class AddResourcesPathsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $container->setParameter('contao.resources_paths', $this->getResourcesPaths($container));
    }

    /**
     * @return string[]
     */
    private function getResourcesPaths(ContainerBuilder $container): array
    {
        $paths = [];
        $rootDir = $container->getParameter('kernel.project_dir');

        $bundles = $container->getParameter('kernel.bundles');
        $meta = $container->getParameter('kernel.bundles_metadata');

        foreach ($bundles as $name => $class) {
            if (ContaoModuleBundle::class === $class) {
                $paths[] = $meta[$name]['path'];
            } elseif (is_dir($path = $meta[$name]['path'].'/Resources/contao')) {
                $paths[] = $path;
            } elseif (is_dir($path = $meta[$name]['path'].'/contao')) {
                $paths[] = $path;
            }
        }

        if (is_dir($rootDir.'/contao')) {
            $paths[] = $rootDir.'/contao';
        }

        if (is_dir($rootDir.'/app/Resources/contao')) {
            @trigger_error('Using "app/Resources/contao" has been deprecated and will no longer work in Contao 5.0. Use the "contao" folder instead.', E_USER_DEPRECATED);
            $paths[] = $rootDir.'/app/Resources/contao';
        }

        if (is_dir($rootDir.'/src/Resources/contao')) {
            @trigger_error('Using "src/Resources/contao" has been deprecated and will no longer work in Contao 5.0. Use the "contao" folder instead.', E_USER_DEPRECATED);
            $paths[] = $rootDir.'/src/Resources/contao';
        }

        return $paths;
    }
}
