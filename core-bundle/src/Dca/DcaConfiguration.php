<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca;

use Contao\CoreBundle\Dca\Definition\Builder\DcaArrayNodeDefinition;
use Contao\CoreBundle\Dca\Definition\Builder\DcaNodeBuilder;
use Contao\DataContainer;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DcaConfiguration implements ConfigurationInterface
{
    private readonly NodeBuilder $nodeBuilder;

    public function __construct(private readonly string $name)
    {
        $this->nodeBuilder = new DcaNodeBuilder();
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder($this->name, 'array', $this->nodeBuilder);

        $tree
            ->getRootNode()
                ->ignoreExtraKeys()
                ->children()
                    ->append($this->addConfigNode())
                    ->append($this->addListNode())
                    ->append($this->addPalettesNode())
                    ->append($this->addSubpalettesNode())
                    ->append($this->addFieldsNode())
        ;

        return $tree;
    }

    private function addConfigNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('config');
        $node->setBuilder($this->nodeBuilder);

        $children = $node->children();

        $children
            ->scalarNode('dataContainer')
            ->isRequired()
            ->cannotBeEmpty()
        ;

        $children
            ->booleanNode('closed')
            ->defaultFalse()
        ;

        $children
            ->booleanNode('enableVersioning')
            ->defaultFalse()
        ;

        $children
            ->scalarNode('ptable')
        ;

        $children
            ->arrayNode('onload_callback')
            ->defaultNull()
            ->prototype('callback')
        ;

        $children
            ->arrayNode('oncut_callback')
            ->defaultNull()
            ->prototype('callback')
        ;

        return $node;
    }

    private function addListNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('list');
        $node->setBuilder($this->nodeBuilder);

        $children = $node->children();

        $children
            ->append($this->addListSortingNode())
        ;

        $children
            ->arrayNode('global_operations')
            ->prototype('operation')
        ;

        $children
            ->arrayNode('operations')
            ->prototype('operation')
        ;

        return $node;
    }

    private function addListSortingNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('sorting');
        $node->setBuilder($this->nodeBuilder);

        $children = $node->children();

        $children
            ->scalarNode('mode')
            ->isRequired()
            ->validate()
                ->ifNotInArray([
                    DataContainer::MODE_SORTED,
                    DataContainer::MODE_SORTABLE,
                    DataContainer::MODE_SORTED_PARENT,
                    DataContainer::MODE_PARENT,
                    DataContainer::MODE_TREE,
                    DataContainer::MODE_TREE_EXTENDED,
                ])
                ->thenInvalid('Invalid sorting mode %s')
        ;

        $children
            ->arrayNode('fields')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->ignoreExtraKeys(true)
            ->prototype('scalar')
            ->validate()
                ->ifTrue(static fn ($value): bool => !\is_string($value))
                ->thenInvalid('Only string values allowed.')
        ;

        $children
            ->node('panelLayout', 'paletteString')
        ;

        $children
            ->node('child_record_callback', 'callback')
        ;

        return $node;
    }

    private function addPalettesNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('palettes');
        $node->setBuilder($this->nodeBuilder);

        $node
            ->children()
                ->arrayNode('__selector__')
                ->ignoreExtraKeys(true)
                ->prototype('scalar')
        ;

        return $node;
    }

    private function addSubpalettesNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('subpalettes');
        $node->setBuilder($this->nodeBuilder);

        $node
            ->prototype('palettestring')
        ;

        return $node;
    }

    private function addFieldsNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('fields');
        $node->setBuilder($this->nodeBuilder);

        $node
            ->prototype('field')
        ;

        return $node;
    }
}
