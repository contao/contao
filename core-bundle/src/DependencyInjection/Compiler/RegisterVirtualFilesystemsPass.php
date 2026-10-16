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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterVirtualFilesystemsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $filesystems = [];
        $metadata = [];

        foreach ($container->findTaggedServiceIds('contao.virtual_filesystem') as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $name = $container->getParameterBag()->resolveValue($attributes['name'] ?? null);

                if (!\is_string($name) || '' === $name || str_contains($name, '/')) {
                    throw new InvalidConfigurationException(\sprintf('The virtual filesystem service "%s" must have a non-empty string name without "/".', $serviceId));
                }

                if (isset($filesystems[$name])) {
                    throw new InvalidConfigurationException(\sprintf('A virtual filesystem with the name "%s" is already defined.', $name));
                }

                unset($attributes['name']);
                $filesystems[$name] = new Reference($serviceId);
                $metadata[$name] = $attributes;
            }
        }

        $container->getDefinition('contao.filesystem.virtual_registry')->setArguments([
            ServiceLocatorTagPass::register($container, $filesystems),
            $metadata,
        ]);
    }
}
