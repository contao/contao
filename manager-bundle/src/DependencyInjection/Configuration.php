<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('contao_manager');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // Backwards compatibility with symfony/config <4.2
            $rootNode = $treeBuilder->root('contao_manager');
        }

        $rootNode
            ->children()
                ->scalarNode('manager_path')
                    ->defaultNull()
                    ->info('The path to the Contao manager relative to the Contao web directory.')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
