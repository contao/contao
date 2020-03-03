<?php

declare(strict_types=1);

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class AddEntityPathsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->setParameter('contao.entity_paths', $this->getEntityPaths($container));
    }

    /**
     * @return array<string>
     */
    private function getEntityPaths(ContainerBuilder $container): array
    {
        $paths = [];
        $rootDir = $container->getParameter('kernel.project_dir');

        $bundles = $container->getParameter('kernel.bundles');
        $meta = $container->getParameter('kernel.bundles_metadata');

        foreach ($bundles as $name => $class) {
            if (ContaoModuleBundle::class === $class) {
                $paths[] = $meta[$name]['path'];
            } elseif (is_dir($path = $meta[$name]['path'].'/Orm')) {
                $paths[] = $path;
            }
        }

        if (is_dir($rootDir.'/src/Orm')) {
            $paths[] = $rootDir.'/src/Orm';
        }

        return $paths;
    }
}
