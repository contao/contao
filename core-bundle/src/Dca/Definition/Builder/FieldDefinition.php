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

class FieldDefinition extends DcaArrayNodeDefinition implements PreconfiguredDefinitionInterface
{
    public function preconfigure(): void
    {
        $builder = $this->getNodeBuilder();

        $builder
            ->booleanNode('exclude')
                ->defaultFalse()
            ->end()
            ->booleanNode('filter')
                ->defaultFalse()
            ->end()
        ;

        $builder
            ->scalarNode('inputType')
                ->validate()
                    ->always(
                        static function ($value) {
                            if (isset($GLOBALS['BE_FFL']) && !isset($GLOBALS['BE_FFL'][$value])) {
                                throw new \InvalidArgumentException(sprintf('The input type "%s" is unknown.', $value));
                            }

                            return $value;
                        },
                    )
        ;

        $builder->callbackNode('options_callback');

        $builder->dcaArrayNode('eval');

        $builder
            ->variableNode('sql')
            ->validate()
                ->always(
                    static function ($value) {
                        if (null !== $value && !\is_string($value) && !\is_array($value)) {
                            throw new \InvalidArgumentException(sprintf('The SQL definition has to be either a string or an array (%s).', json_encode($value, JSON_THROW_ON_ERROR)));
                        }

                        return $value;
                    },
                )
        ;
    }
}
