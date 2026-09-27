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

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Creates an app cache that is shared across environments.
 */
class SharedAppCachePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('cache.app')) {
            return;
        }

        $definition = new ChildDefinition('cache.app');

        // Setting a specific namespace will make the cache entries available
        // across all environments.
        $definition->addTag('cache.pool', [
            'namespace' => 'contao',
        ]);

        $appDefinition = $container->findDefinition('cache.app');

        if ($appDefinition instanceof ChildDefinition) {
            $parent = $appDefinition->getParent();

            // We need to override the $directory argument of the filesystem cache, otherwise
            // the cache entries will again be only available in the respective environment.
            if ('cache.adapter.filesystem' === $parent) {
                $definition->setArgument('$directory', '%kernel.project_dir%/var/cache/shared');
            }
        }

        $container->setDefinition('contao.cache.shared', $definition);
    }
}
