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

use Contao\CoreBundle\Util\PackageUtil;
use PackageVersions\Versions;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LayoutAssetsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.listener.data_container.layout_assets')) {
            return;
        }

        $configs = $container->getExtensionConfig('framework');

        foreach ($configs as $config) {
            if (!isset($config['assets']['json_manifest_path'])) {
                continue;
            }

            $definition = $container->getDefinition('contao.listener.data_container.layout_assets');
            $definition->setArgument(0, $config['assets']['json_manifest_path']);

            // Required to get the service in PageRegular
            $container->getDefinition('assets.packages')->setPublic(true);

            break;
        }
    }
}
