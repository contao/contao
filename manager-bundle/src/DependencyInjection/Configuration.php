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
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('contao_manager');
        $rootNode
            ->children()
                ->scalarNode('path')
                    ->defaultValue('contao-manager.phar.php')
                    ->info('The path to the Contao Manager relative to the web directory.')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
