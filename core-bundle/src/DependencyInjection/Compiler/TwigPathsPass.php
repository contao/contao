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

use Contao\CoreBundle\Twig\Interop\ContaoTwigTemplateLocator;
use Contao\CoreBundle\Twig\Loader\FilesystemLoader;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
class TwigPathsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $baseLoader = $container->getDefinition('twig.loader.native_filesystem');
        $loader = $container->getDefinition(FilesystemLoader::class);

        $this->migrateAddPathCalls($baseLoader, $loader);
        $this->registerContaoTwigTemplates($container, $loader);
    }

    /**
     * Rewires the registered "addPath" method calls to our filesystem loader.
     */
    private function migrateAddPathCalls(Definition $from, Definition $to): void
    {
        $calls = array_filter(
            $from->getMethodCalls(),
            static function (array $call): bool {
                return 'addPath' === $call[0];
            }
        );

        if (empty($calls)) {
            return;
        }

        $from->removeMethodCall('addPath');

        foreach ($calls as $call) {
            $to->addMethodCall(...$call);
        }
    }

    /**
     * Find template locations for overwritten Contao templates and register
     * them under the 'ContaoLegacy' and 'ContaoLegacy_<theme>' namespaces.
     */
    private function registerContaoTwigTemplates(ContainerBuilder $container, Definition $loader): void
    {
        $defaultPath = $container->getParameterBag()->resolveValue('%twig.default_path%');
        $bundleMetadata = $container->getParameter('kernel.bundles_metadata');

        $locator = new ContaoTwigTemplateLocator();

        $basePaths = array_filter(
            array_merge(
                [$locator->getAppPath($defaultPath)],
                array_values($locator->getBundlePaths($bundleMetadata)),
            )
        );

        $addPath = static function (string $path, string $namespace) use ($container, $loader): void {
            $container->addResource(new FileExistenceResource($path));

            $loader->addMethodCall('addPath', [$path, $namespace]);
        };

        foreach ($basePaths as $path) {
            $addPath($path, 'ContaoLegacy');
        }

        foreach ($locator->getAppThemePaths($defaultPath) as $theme => $path) {
            $addPath($path, "ContaoLegacy_$theme");
        }
    }
}
