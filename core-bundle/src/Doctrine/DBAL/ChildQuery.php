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

final class ChildQuery
{
    /**
     * @var list<string>
     */
    private array $columns = [];

    public function __construct(
        private string|null $orderBy = null,
        private string $where = '',
    ) {
    }

    public function withOrderBy(string $column): self
    {
        $clone = clone $this;
        $clone->orderBy = $column;

        return $clone;
    }

    /**
     * Do not pass untrusted input to this method.
     */
    public function withWhere(string $where): self
    {
        $clone = clone $this;
        $clone->where = $where;

        return $clone;
    }

    public function withColumns(string ...$columns): self
    {
        $clone = clone $this;
        $clone->columns = array_values(array_unique($columns));

        return $clone;
    }

    public function orderBy(): string|null
    {
        return $this->orderBy;
    }

    public function where(): string
    {
        return $this->where;
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return $this->columns;
    }
}
