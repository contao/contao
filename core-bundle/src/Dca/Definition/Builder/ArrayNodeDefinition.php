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

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition as StrictArrayNodeDefinition;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * Custom array node definition with access to the DcaNodeBuilder and custom prototypes.
 *
 * @method DcaNodeBuilder getNodeBuilder()
 */
class ArrayNodeDefinition extends StrictArrayNodeDefinition
{
    protected static array $nullables = [];

    public function callbackPrototype(): CallbackDefinition
    {
        return $this->prototype = $this->getNodeBuilder()->callbackNode(null)
            ->setParent($this)
        ;
    }

    public function operationPrototype(): OperationDefinition
    {
        return $this->prototype = $this->getNodeBuilder()->operationNode(null)
            ->setParent($this)
        ;
    }

    public function dcaNodePrototype(string $type): DcaNodeDefinition
    {
        return $this->prototype = $this->getNodeBuilder()->dcaNode(null, $type)
            ->setParent($this)
        ;
    }

    /**
     * Customize the node creation to allow nullable prototype array nodes that get removed if not set.
     */
    protected function createNode(): NodeInterface
    {
        if ($nullable = ($this->default && null === $this->defaultValue)) {
            $this->default = [];
        }

        $node = parent::createNode();

        if ($nullable && $node instanceof BaseNode) {
            $parent = $node->getParent();

            if ($parent instanceof ArrayNode) {
                static::$nullables[spl_object_id($parent)][] = $node->getName();

                $parent->setFinalValidationClosures([static function ($value) use ($parent) {
                    foreach (static::$nullables[spl_object_id($parent)] as $name) {
                        if (\is_array($value[$name] ?? null) && empty($value[$name])) {
                            unset($value[$name]);
                        }
                    }

                    return $value;
                }]);
            }
        }

        return $node;
    }
}
