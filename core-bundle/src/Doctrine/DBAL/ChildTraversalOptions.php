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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;

final class ChildTraversalOptions extends AbstractTraversalOptions
{
    /**
     * @var list<mixed>
     */
    private array $whereParameters = [];

    /**
     * @var list<string|Type|ParameterType|ArrayParameterType>
     */
    private array $whereTypes = [];

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
     * Replaces the condition and its bindings. Applied at every level, so a
     * non-matching row and all its descendants are excluded from traversal. Do not
     * pass untrusted input as SQL. Bind values using positional ? placeholders.
     *
     * @param list<mixed>                                        $parameters
     * @param list<string|Type|ParameterType|ArrayParameterType> $types      Omitted types default to STRING
     */
    public function withWhere(string $where, array $parameters = [], array $types = []): self
    {
        $clone = clone $this;
        $clone->where = $where;
        $clone->whereParameters = $parameters;
        $clone->whereTypes = array_replace(array_fill(0, \count($parameters), ParameterType::STRING), $types);

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
     * @return list<mixed>
     */
    public function whereParameters(): array
    {
        return $this->whereParameters;
    }

    /**
     * @return list<string|Type|ParameterType|ArrayParameterType>
     */
    public function whereTypes(): array
    {
        return $this->whereTypes;
    }
}
