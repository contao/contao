<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Definition\Builder;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeParentInterface;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * Custom VariableNodeDefinition as a transitional solution to only
 * trigger a deprecation instead of an exception for incorrectly configured
 * nodes.
 *
 * The concrete value of the node is determined via the $inner node.
 *
 * If an invalid value is detected, the default value or a provided
 * invalidFallbackValue will be used instead.
 *
 * @internal
 *
 * @method NodeParentInterface|NodeBuilder|NodeDefinition|ArrayNodeDefinition|VariableNodeDefinition|DcaNodeBuilder|null end()
 */
class DcaNodeDefinition extends VariableNodeDefinition
{
    use FailableNodeDefinitionTrait;
    use RootAwareTrait;

    private NodeInterface|null $innerNode = null;

    public function __construct(
        string|null $name,
        private readonly NodeDefinition $inner,
        NodeParentInterface|null $parent = null,
    ) {
        parent::__construct($name, $parent);
    }

    public function cannotBeEmpty(): static
    {
        $this->inner->allowEmptyValue = false;

        return $this;
    }

    public function getNode(bool $forceRootNode = false): NodeInterface
    {
        $this
            ->beforeNormalization()
            ->always(
                $this->failableNormalizationCallback(
                    function ($value) {
                        if (!$this->innerNode) {
                            $this->inner->parent = $this->parent;

                            if ($this->inner instanceof PreconfiguredDefinitionInterface) {
                                $this->inner->preconfigure();
                            }

                            if ($this->validation) {
                                $this->inner->validation = $this->validation;
                            }

                            $this->innerNode = $this->inner->getNode();
                        }

                        return $this->innerNode->normalize($value);
                    },
                ),
            )
        ;

        return parent::getNode($forceRootNode);
    }
}
