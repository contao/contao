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

use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;

/**
 * Preconfigured ScalarNodeDefinition for a DCA palette string.
 */
class PaletteStringDefinition extends ScalarNodeDefinition implements PreconfiguredDefinitionInterface
{
    public function preconfigure(): void
    {
        $this
            ->defaultNull()
            ->validate()
            ->ifTrue(
                static function (?string $value) {
                    // TODO: Add validation.
                    return false;
                },
            )
            ->thenInvalid('Invalid palette string.')
        ;
    }
}
