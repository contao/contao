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

final class HierarchyDefinition
{
    private string|null $scopeColumn = null;

    private int|string|null $scopeValue = null;

    private bool $optionalScope = false;

    public function __construct(
        private readonly string $table,
        private readonly string $idColumn,
        private readonly string $parentColumn,
    ) {
    }

    public function withScope(string $column, int|string $value): self
    {
        $clone = clone $this;
        $clone->scopeColumn = $column;
        $clone->scopeValue = $value;
        $clone->optionalScope = false;

        return $clone;
    }

    public function withOptionalScope(string $column, int|string $value): self
    {
        $clone = $this->withScope($column, $value);
        $clone->optionalScope = true;

        return $clone;
    }

    public function table(): string
    {
        return $this->table;
    }

    public function idColumn(): string
    {
        return $this->idColumn;
    }

    public function parentColumn(): string
    {
        return $this->parentColumn;
    }

    public function scopeColumn(): string|null
    {
        return $this->scopeColumn;
    }

    public function scopeValue(): int|string|null
    {
        return $this->scopeValue;
    }

    public function hasOptionalScope(): bool
    {
        return $this->optionalScope;
    }
}
