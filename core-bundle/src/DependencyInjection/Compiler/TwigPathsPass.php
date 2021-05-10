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

use Contao\CoreBundle\Twig\Inheritance\ContaoTwigTemplateLocator;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchy;
use Contao\CoreBundle\Twig\Loader\FilesystemLoader;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class TwigPathsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->migrateAddPathCalls($container);
        $this->registerContaoTwigTemplates($container);
    }

    /**
     * Rewires the registered "addPath" method calls to our filesystem loader.
     */
    private function migrateAddPathCalls(ContainerBuilder $container): void
    {
        $from = $container->getDefinition('twig.loader.native_filesystem');
        $to = $container->getDefinition(FilesystemLoader::class);

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
     * Registers Contao Twig templates and template paths.
     */
    private function registerContaoTwigTemplates(ContainerBuilder $container): void
    {
        $loader = $container->getDefinition(FilesystemLoader::class);
        $templateHierarchy = $container->getDefinition(TemplateHierarchy::class);

        $defaultPath = $container->getParameterBag()->resolveValue('%twig.default_path%');
        $bundleMetadata = $container->getParameter('kernel.bundles_metadata');

        $templateLocator = new ContaoTwigTemplateLocator();

        $registerPath = static function (string $path, string $namespace) use ($loader): void {
            $loader->addMethodCall(
                'addPath',
                [$path, $namespace]
            );
        };

        // App paths
        foreach ($templateLocator->getAppThemePaths($defaultPath) as $themeSlug => $path) {
            $registerPath($path, TemplateHierarchy::getAppThemeNamespace($themeSlug));

            $templateHierarchy->addMethodCall(
                'setAppThemeTemplates',
                [$templateLocator->findTemplates($path), $themeSlug]
            );
        }

        if (null !== ($path = $templateLocator->getAppPath($defaultPath))) {
            $registerPath($path, 'Contao');
            $registerPath($path, TemplateHierarchy::getAppNamespace());

            $container->addResource(new FileExistenceResource($path));

            $templateHierarchy->addMethodCall(
                'setAppTemplates',
                [$templateLocator->findTemplates($path)]
            );
        }

        // Bundle paths (loaded later = higher priority)
        foreach (array_reverse($templateLocator->getBundlePaths($bundleMetadata)) as $bundle => $path) {
            $registerPath($path, 'Contao');
            $registerPath($path, TemplateHierarchy::getBundleNamespace($bundle));

            $container->addResource(new FileExistenceResource($path));

            $templateHierarchy->addMethodCall(
                'setBundleTemplates',
                [$templateLocator->findTemplates($path), $bundle]
            );
        }

        // todo: do we need DirectoryResource instead of FileExistenceResource
        //       for some resources? (tracking changes inside files)
    }
}
