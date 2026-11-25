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
    /**
     * @var array<string, int|string>
     */
    private array $scopes = [];

    public function __construct(
        private readonly string $table,
        private readonly string $idColumn,
        private readonly string $parentColumn,
    ) {
    }

    /**
     * Replaces all scope conditions. All conditions must match for a row to belong to
     * the hierarchy. An empty map removes the scope restriction.
     *
     * @param array<string, int|string> $scopes Column/value equality conditions combined with AND
     */
    public function withScopes(array $scopes): self
    {
        $clone = clone $this;
        $clone->scopes = $scopes;

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

    /**
     * @return array<string, int|string>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }
}
