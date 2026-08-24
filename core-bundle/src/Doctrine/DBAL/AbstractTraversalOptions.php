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

abstract class AbstractTraversalOptions
{
    /**
     * @var list<string>
     */
    private array $columns = [];

    private bool $allColumns = false;

    private int|null $maxDepth = null;

    public function withColumns(string ...$columns): static
    {
        $clone = clone $this;
        $clone->columns = array_values(array_unique($columns));
        $clone->allColumns = false;

        return $clone;
    }

    public function withAllColumns(): static
    {
        $clone = clone $this;
        $clone->columns = [];
        $clone->allColumns = true;

        return $clone;
    }

    public function withMaxDepth(int $maxDepth): static
    {
        if ($maxDepth < 1) {
            throw new \InvalidArgumentException('The maximum depth must be greater than zero.');
        }

        $clone = clone $this;
        $clone->maxDepth = $maxDepth;

        return $clone;
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    public function includesAllColumns(): bool
    {
        return $this->allColumns;
    }

    public function maxDepth(): int|null
    {
        return $this->maxDepth;
    }
}
