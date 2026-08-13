<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\Doctrine\DBAL\ChildQuery;
use Contao\CoreBundle\Doctrine\DBAL\Hierarchy;
use Contao\CoreBundle\Doctrine\DBAL\HierarchyDefinition;
use Contao\CoreBundle\Doctrine\DBAL\ParentQuery;

class DcaHierarchy
{
    public function __construct(private readonly Hierarchy $hierarchy)
    {
    }

    /**
     * @param int|list<int|string> $parentIds
     *
     * @return list<int>
     */
    public function getChildIds(array|int $parentIds, string $table, ChildQuery|null $query = null): array
    {
        return array_map(static fn (array $row): int => (int) $row['id'], $this->getChildRows($parentIds, $table, $query));
    }

    /**
     * @param int|list<int|string> $parentIds
     *
     * @return list<array<string, mixed>>
     */
    public function getChildRows(array|int $parentIds, string $table, ChildQuery|null $query = null): array
    {
        $parentIds = array_values(array_filter(array_map(intval(...), (array) $parentIds)));

        if ([] === $parentIds) {
            return [];
        }

        return $this->normalizeRows($this->hierarchy->getChildRows($parentIds, $this->createDefinition($table), $query));
    }

    /**
     * @return list<int>
     */
    public function getParentIds(int $id, string $table, bool $skipId = false): array
    {
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $this->getParentRows($id, $table));

        return $skipId ? array_values(array_filter($ids, static fn (int $parentId): bool => $parentId !== $id)) : $ids;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getParentRows(int $id, string $table, ParentQuery|null $query = null): array
    {
        if ($id <= 0) {
            return [];
        }

        return $this->normalizeRows($this->hierarchy->getParentRows($id, $this->createDefinition($table), $query));
    }

    /**
     * @return array{0: string, 1: int}
     *
     * @throws \RuntimeException if a parent record is not found
     */
    public function getParentTableAndId(int $id, string $table): array
    {
        $query = new ParentQuery()->withColumns('ptable')->withBoundaryRow();
        $rows = $this->getParentRows($id, $table, $query);
        $parent = end($rows);

        if (!$parent || !isset($parent['ptable']) || $table === $parent['ptable']) {
            throw new \RuntimeException(\sprintf('Parent record of %s.%s not found', $table, $id));
        }

        return [(string) $parent['ptable'], (int) $parent['pid']];
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(
            static fn (array $row): array => [...$row, 'id' => (int) $row['id'], 'pid' => (int) $row['pid']],
            $rows,
        );
    }

    private function createDefinition(string $table): HierarchyDefinition
    {
        return new HierarchyDefinition($table, 'id', 'pid')->withOptionalScope('ptable', $table);
    }
}
