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
    protected $ignoreExtraKeys = false;

    protected $removeExtraKeys = true;

    public function preconfigure(): void
    {
        $this
            ->getNodeBuilder()
            ->variableNode('label')->end()
            ->scalarNode('href')->end()
            ->scalarNode('icon')->end()
            ->scalarNode('class')->end()
            ->scalarNode('attributes')->end()
            ->node('button_callback', 'callback')
        ;
    }
}
