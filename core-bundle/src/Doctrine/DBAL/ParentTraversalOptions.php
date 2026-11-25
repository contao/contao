<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\DBAL;

final class ParentTraversalOptions extends AbstractTraversalOptions
{
    private bool $includeBoundary = false;

    /**
     * Includes the first row that fails any scope condition, then stops that parent trail.
     */
    public function withBoundaryRow(): self
    {
        $clone = clone $this;
        $clone->includeBoundary = true;

        return $clone;
    }

    public function includesBoundaryRow(): bool
    {
        return $this->includeBoundary;
    }
}
