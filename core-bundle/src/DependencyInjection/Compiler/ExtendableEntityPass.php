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

use Contao\CoreBundle\Doctrine\ORM\ExtendableEntity\ExtensionRegistry;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtendableEntityPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ExtensionRegistry::class)) {
            return;
        }

        $extensions = $this->getExtensions($container);

        if (empty($extensions)) {
            return;
        }

        $definition = $container->getDefinition(ExtensionRegistry::class);
        $definition->addMethodCall('setExtensions', [$extensions]);
    }

    private function getExtensions(ContainerBuilder $container): array
    {
        $extensions = [];
        $serviceIds = $container->findTaggedServiceIds('contao.extendable_entity');

        foreach ($serviceIds as $serviceId => $tags) {
            if ($container->hasAlias($serviceId)) {
                $serviceId = (string) $container->getAlias($serviceId);
            }

            foreach ($tags as $attributes) {
                if (!isset($attributes['entity'])) {
                    throw new InvalidConfigurationException(
                        sprintf('Missing entity attribute in tagged hook service with service id "%s"', $serviceId)
                    );
                }

                $extensions[$attributes['entity']] = $container->getDefinition($serviceId)->getClass();
            }
        }

        return $extensions;
    }
}
