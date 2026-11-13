<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\WebhooksBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('webhooks');

        $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('request_body_limit')->min(1)->defaultValue(1048576)->end()
                ->integerNode('rate_limit')->min(1)->defaultValue(60)->end()
                ->integerNode('connect_timeout')->min(1)->defaultValue(10)->end()
                ->integerNode('total_timeout')->min(1)->defaultValue(15)->end()
                ->integerNode('max_redirects')->min(0)->defaultValue(0)->end()
                ->booleanNode('allow_private_networks')->defaultFalse()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
