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

/**
 * Preconfigured ArrayNodeDefinition for a DCA operation.
 */
class OperationDefinition extends DcaArrayNodeDefinition implements PreconfiguredDefinitionInterface
{
    public function preconfigure(): void
    {
        $builder = $this->getNodeBuilder();

        $builder
            ->variableNode('label')->end()
            ->scalarNode('href')->end()
            ->scalarNode('icon')->end()
            ->scalarNode('class')->end()
            ->scalarNode('attributes')->end()
            ->booleanNode('hidden')->end()
        ;

        $builder
            ->dcaNode('disabled', 'boolean')
        ;

        $builder
            ->dcaNode('reverse', 'boolean')
            ->invalidFallbackValue(false)
        ;

        $builder->node('permission_callback', 'callback')->end();
        $builder->node('button_callback', 'callback')->end();
    }
}
