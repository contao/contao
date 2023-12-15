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

use Contao\CoreBundle\Dca\Definition\Builder\ArrayNodeDefinition;
use Contao\CoreBundle\Dca\Definition\Builder\DcaArrayNodeDefinition;
use Contao\CoreBundle\Dca\Definition\Builder\DcaNodeBuilder;
use Contao\CoreBundle\Dca\Definition\Builder\DcaTreeBuilder;
use Contao\DataContainer;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DcaConfiguration implements ConfigurationInterface
{
    private readonly NodeBuilder $nodeBuilder;

    // TODO: Set to false in Contao 6
    private bool $allowFailingNodes = true;

    public function __construct(private readonly string $name)
    {
        $this->nodeBuilder = new DcaNodeBuilder();
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new DcaTreeBuilder($this->name, $this->nodeBuilder);

        $tree
            ->allowFailingNodes($this->allowFailingNodes)
            ->getRootNode()
                ->children()
                    ->append($this->addConfigNode())
                    ->append($this->addListNode())
                    ->append($this->addPalettesNode())
                    ->append($this->addSubpalettesNode())
                    ->append($this->addFieldsNode())
        ;

        return $tree;
    }

    public function allowFailingNodes(bool $allow): static
    {
        $this->allowFailingNodes = $allow;

        return $this;
    }

    private function addConfigNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('config');
        $node
            ->info('Configuration for the DCA resource itself')
            ->setBuilder($this->nodeBuilder)
        ;

        $children = $node->children();

        $children
            ->dcaNode('dataContainer')
            ->info('Classname of the driver')
            ->example('\Contao\DC_Table')
            ->isRequired()
            ->cannotBeEmpty()
        ;

        $children
            ->dcaNode('closed', 'boolean')
            ->info('If true, you cannot add further records to the table.')
            ->defaultFalse()
        ;

        $children
            ->dcaNode('enableVersioning', 'boolean')
            ->defaultFalse()
            ->info('If true, Contao saves the old version of a record when a new version is created.')
        ;

        $children
            ->dcaNode('ptable', 'scalar')
            ->defaultNull()
            ->info('Name of the related parent table (table.pid = ptable.id).')
        ;

        $children
            ->dcaArrayNode('onload_callback')
            ->info('Callbacks called when a DataContainer is initialized. Passes the DataContainer object as argument.')
            ->example("['tl_content', 'adjustDcaByType']")
            ->callbackPrototype()
        ;

        $children
            ->dcaArrayNode('oncut_callback')
            ->info('Callbacks called when a record is moved and passes the DataContainer object as argument.')
            ->example("['tl_page', 'scheduleUpdate']")
            ->callbackPrototype()
        ;

        return $node;
    }

    private function addListNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('list');
        $node->setBuilder($this->nodeBuilder);

        $node
            ->append($this->addListSortingNode())->end()
        ;

        $children = $node->children();

        $children
            ->dcaArrayNode('global_operations')
            ->prototype('operation')
        ;

        $children
            ->dcaArrayNode('operations')
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
            ->dcaNode('mode', 'scalar')
            ->defaultValue(DataContainer::MODE_UNSORTED)
            ->validate()
                ->ifNotInArray([
                    DataContainer::MODE_UNSORTED,
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
            ->dcaArrayNode('fields')
            ->dcaNodePrototype('scalar')
            ->invalidFallbackValue('')
            ->validate()
                ->ifTrue(static fn ($value): bool => !\is_string($value))
                ->thenInvalid('Only string values allowed.')
        ;

        $children
            ->dcaNode('panelLayout', 'paletteString')
        ;

        $children
            ->dcaNode('child_record_callback', 'callback')
        ;

        return $node;
    }

    private function addPalettesNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('palettes');
        $node->setBuilder($this->nodeBuilder);

        $node
            ->children()
                ->dcaArrayNode('__selector__')
                ->ignoreExtraKeys()
                ->dcaNodePrototype('scalar')
        ;

        return $node;
    }

    private function addSubpalettesNode(): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition('subpalettes');
        $node->setBuilder($this->nodeBuilder);

        $node
            ->dcaNodePrototype('palettestring')
        ;

        return $node;
    }

    private function addFieldsNode(): ArrayNodeDefinition
    {
        // No need to use DcaArrayNodeDefinition since fields always had to be an array
        $node = new ArrayNodeDefinition('fields');
        $node->setBuilder($this->nodeBuilder);

        $node
            ->dcaNodePrototype('field')
        ;

        return $node;
    }
}
