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
    public function __construct(
        private bool $orderBySorting = false,
        private string $where = '',
    ) {
    }

    public function withOrderBySorting(bool $orderBySorting = true): self
    {
        $clone = clone $this;
        $clone->orderBySorting = $orderBySorting;

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

    public function orderBySorting(): bool
    {
        return $this->orderBySorting;
    }

    public function where(): string
    {
        return $this->where;
    }
}
