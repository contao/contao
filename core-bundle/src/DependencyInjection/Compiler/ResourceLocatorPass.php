<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds services tagged contao.resource_locator as Contao resource locators.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ResourceLocatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Register additional resource locators
        $locatorIds = $container->findTaggedServiceIds('contao.resource_locator');

        if (count($locatorIds) === 0) {
            throw new LogicException('No Contao resource locators found. You need to tag at least one locator with "contao.resource_locator"');
        }

        if (count($locatorIds) === 1) {
            $container->setAlias('contao.resource_locator', key($locatorIds));
        } else {
            $chainLoader = $container->getDefinition('contao.resource_locator.chain');
            foreach (array_keys($locatorIds) as $id) {
                $chainLoader->addMethodCall('addLocator', array(new Reference($id)));
            }
            $container->setAlias('contao.resource_locator', 'contao.resource_locator.chain');
        }
    }
}
