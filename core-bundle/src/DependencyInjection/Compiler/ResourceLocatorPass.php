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
        $locatorIds = $this->getLocators($container);

        if (count($locatorIds) === 1) {
            $alias = key($locatorIds);
        } else {
            $chainLocator = $container->getDefinition('contao.resource_locator.chain');

            foreach ($this->getPriorizedLocators($locatorIds) as $locators) { // FIXME: array_flatten?
                foreach ($locators as $locator) {
                    $chainLocator->addMethodCall('addLocator', [new Reference($locator)]);
                }
            }

            $alias = 'contao.resource_locator.chain';
        }

        $container->setAlias('contao.resource_locator', $alias);
    }

    /**
     * Gets tagged locators from container builder.
     *
     * @param ContainerBuilder $container The container object
     *
     * @return array The tagged locators
     *
     * @throws \LogicException If there are no Contao resource locators
     */
    private function getLocators(ContainerBuilder $container)
    {
        $locatorIds = $container->findTaggedServiceIds('contao.resource_locator');

        if (count($locatorIds) === 0) {
            throw new LogicException('No Contao resource locators found. You need to tag at least one locator with "contao.resource_locator"');
        }

        return $locatorIds;
    }

    /**
     * Orders the locators by priority and returns a nested array of locators.
     *
     * @param array $locators The locators array
     *
     * @return array The priorized locators array
     */
    private function getPriorizedLocators(array $locators)
    {
        $prioritizedLocators = [];

        foreach ($locators as $id => $tags) {
            foreach ($tags as $tag) {
                $priority = isset($tag['priority']) ? $tag['priority'] : 0;
                $prioritizedLocators[$priority][] = $id;
            }
        }

        krsort($prioritizedLocators);

        return $prioritizedLocators;
    }
}
