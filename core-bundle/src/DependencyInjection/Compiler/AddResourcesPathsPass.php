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

        foreach ($container->getParameter('kernel.bundles') as $name => $class) {
            if (ContaoModuleBundle::class === $class) {
                $paths[] = sprintf('%s/system/modules/%s', $rootDir, $name);
            } elseif (null !== ($path = $this->getResourcesPathFromClassName($class))) {
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

    private function getResourcesPathFromClassName(string $class): ?string
    {
        $reflection = new \ReflectionClass($class);

        if (is_dir($dir = \dirname($reflection->getFileName()).'/Resources/contao')) {
            return $dir;
        }

        return null;
    }
}
