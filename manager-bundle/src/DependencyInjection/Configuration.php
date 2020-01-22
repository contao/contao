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
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('contao_manager');
        $treeBuilder
            ->getRootNode()
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
