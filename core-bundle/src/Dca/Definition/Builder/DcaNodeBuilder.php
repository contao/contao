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

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * @method NodeDefinition|DcaNodeBuilder|ArrayNodeDefinition node(?string $name, string $type)()
 * @method ArrayNodeDefinition                               arrayNode(?string $name)()
 */
class DcaNodeBuilder extends NodeBuilder
{
    public function __construct()
    {
        parent::__construct();

        $this->nodeMapping['array'] = ArrayNodeDefinition::class;
        $this->nodeMapping['callback'] = CallbackDefinition::class;
        $this->nodeMapping['dca'] = DcaArrayNodeDefinition::class;
        $this->nodeMapping['field'] = FieldDefinition::class;
        $this->nodeMapping['operation'] = OperationDefinition::class;
        $this->nodeMapping['palettestring'] = PaletteStringDefinition::class;
    }

    public function dcaArrayNode(string|null $name): DcaArrayNodeDefinition
    {
        $node = new DcaArrayNodeDefinition($name);
        $this->append($node);

        return $node;
    }

    public function callbackNode(string|null $name): CallbackDefinition
    {
        $node = new CallbackDefinition($name);
        $this->append($node);

        return $node;
    }

    public function operationNode(string|null $name): OperationDefinition
    {
        $node = new OperationDefinition($name);
        $this->append($node);

        return $node;
    }

    public function append(NodeDefinition $node): static
    {
        parent::append($node);

        if ($node instanceof PreconfiguredDefinitionInterface) {
            $node->preconfigure();
        }

        return $this;
    }
}
