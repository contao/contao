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

final class ParentQuery
{
    /**
     * @var list<string>
     */
    private array $columns = [];

    private bool $includeBoundary = false;

    public function withColumns(string ...$columns): self
    {
        $clone = clone $this;
        $clone->columns = array_values(array_unique($columns));

        return $clone;
    }

    public function withBoundaryRow(): self
    {
        $clone = clone $this;
        $clone->includeBoundary = true;

        return $clone;
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    public function includesBoundaryRow(): bool
    {
        return $this->includeBoundary;
    }
}
