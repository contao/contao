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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;

class Hierarchy
{
    /**
     * @var array<string, bool>
     */
    private array $hasParentTableColumn = [];

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @param int|list<int|string> $parentIds
     *
     * @return list<int>
     */
    public function getChildIds(array|int $parentIds, string $table, ChildQuery|null $query = null): array
    {
        $parentIds = $this->normalizeIds((array) $parentIds);
        $query ??= new ChildQuery();

        if ([] === $parentIds) {
            return [];
        }

        $rows = $this->supportsRecursiveCommonTableExpressions()
            ? $this->fetchChildrenUsingCommonTableExpression($parentIds, $table, $query)
            : $this->fetchChildrenIteratively($parentIds, $table, $query);

        return $this->sortChildren($rows, $parentIds, $query->orderBySorting());
    }

    /**
     * @return list<int>
     */
    public function getParentIds(int $id, string $table, bool $skipId = false): array
    {
        if ($id <= 0) {
            return [];
        }

        if ($this->supportsRecursiveCommonTableExpressions()) {
            $ids = $this->sortParents($this->fetchParentsUsingCommonTableExpression($id, $table), $id);
        } else {
            $ids = $this->fetchParentIdsUsingUnion($id, $table);
        }

        return $skipId ? array_values(array_diff($ids, [$id])) : $ids;
    }

    /**
     * @param list<int> $parentIds
     *
     * @return list<array<string, mixed>>
     */
    private function fetchChildrenUsingCommonTableExpression(array $parentIds, string $table, ChildQuery $query): array
    {
        $quotedTable = $this->connection->quoteIdentifier($table);
        $rootSorting = $query->orderBySorting() ? 'sorting' : '0 AS sorting';
        $childSorting = $query->orderBySorting() ? 'sorting' : '0 AS sorting';
        $rootCondition = $this->getParentTableCondition($table);
        $childCondition = $this->getParentTableCondition($table);
        $where = $this->getWhereCondition($query);

        $sql = <<<SQL
            WITH RECURSIVE contao_tree (id, pid, sorting) AS (
                SELECT id, pid, $rootSorting FROM $quotedTable WHERE pid IN (?)$rootCondition$where
                UNION DISTINCT
                SELECT child.id, child.pid, child.sorting FROM (
                    SELECT id, pid, $childSorting FROM $quotedTable WHERE 1 = 1$childCondition$where
                ) child
                    INNER JOIN contao_tree parent ON child.pid = parent.id
            )
            SELECT id, pid, sorting FROM contao_tree
            SQL;

        return $this->connection->fetchAllAssociative($sql, [$parentIds], [ArrayParameterType::INTEGER]);
    }

    /**
     * @param list<int> $parentIds
     *
     * @return list<array<string, mixed>>
     */
    private function fetchChildrenIteratively(array $parentIds, string $table, ChildQuery $query): array
    {
        $quotedTable = $this->connection->quoteIdentifier($table);
        $sorting = $query->orderBySorting() ? 'sorting' : '0 AS sorting';
        $parentTableCondition = $this->getParentTableCondition($table);
        $where = $this->getWhereCondition($query);
        $rows = [];
        $pendingIds = $parentIds;

        while ([] !== $pendingIds) {
            $result = $this->connection->fetchAllAssociative(
                "SELECT id, pid, $sorting FROM $quotedTable WHERE pid IN (?)$parentTableCondition$where",
                [$pendingIds],
                [ArrayParameterType::INTEGER],
            );
            $pendingIds = [];

            foreach ($result as $row) {
                $id = (int) $row['id'];

                if (isset($rows[$id])) {
                    continue;
                }

                $rows[$id] = $row;
                $pendingIds[] = $id;
            }
        }

        return array_values($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchParentsUsingCommonTableExpression(int $id, string $table): array
    {
        $table = $this->connection->quoteIdentifier($table);
        $sql = <<<SQL
            WITH RECURSIVE contao_tree (id, pid) AS (
                SELECT id, pid FROM $table WHERE id = ?
                UNION DISTINCT
                SELECT parent.id, parent.pid FROM $table parent
                    INNER JOIN contao_tree child ON parent.id = child.pid
            )
            SELECT id, pid FROM contao_tree
            SQL;

        return $this->connection->fetchAllAssociative($sql, [$id]);
    }

    /**
     * @return list<int>
     */
    private function fetchParentIdsUsingUnion(int $id, string $table): array
    {
        $table = $this->connection->quoteIdentifier($table);
        $query = "SELECT id, @pid := pid FROM $table WHERE id = ?".str_repeat(" UNION SELECT id, @pid := pid FROM $table WHERE id = @pid", 9);
        $ids = [];
        $seen = [];
        $currentId = $id;

        while ($currentId > 0 && !isset($seen[$currentId])) {
            $batch = array_map(intval(...), $this->connection->fetchFirstColumn($query, [$currentId]));

            if ([] === $batch) {
                break;
            }

            foreach ($batch as $parentId) {
                if (!isset($seen[$parentId])) {
                    $seen[$parentId] = true;
                    $ids[] = $parentId;
                }
            }

            if (10 !== \count($batch)) {
                break;
            }

            $currentId = end($batch);
        }

        return $ids;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<int>                  $parentIds
     *
     * @return list<int>
     */
    private function sortChildren(array $rows, array $parentIds, bool $orderBySorting): array
    {
        $children = [];

        foreach ($rows as $row) {
            $children[(int) $row['pid']][] = ['id' => (int) $row['id'], 'sorting' => (int) $row['sorting']];
        }

        if ($orderBySorting) {
            foreach ($children as &$siblings) {
                usort($siblings, static fn (array $a, array $b): int => [$a['sorting'], $a['id']] <=> [$b['sorting'], $b['id']]);
            }
            unset($siblings);

            return $this->sortChildrenDepthFirst($children, $parentIds);
        }

        $ids = [];
        $seen = [];
        $pendingIds = $parentIds;

        while ([] !== $pendingIds) {
            $nextIds = [];

            foreach ($pendingIds as $parentId) {
                foreach ($children[$parentId] ?? [] as $child) {
                    if (isset($seen[$child['id']])) {
                        continue;
                    }

                    $seen[$child['id']] = true;
                    $ids[] = $child['id'];
                    $nextIds[] = $child['id'];
                }
            }

            $pendingIds = $nextIds;
        }

        return $ids;
    }

    /**
     * @param array<int, list<array{id: int, sorting: int}>> $children
     * @param list<int>                                      $parentIds
     *
     * @return list<int>
     */
    private function sortChildrenDepthFirst(array $children, array $parentIds): array
    {
        $ids = [];
        $seen = [];
        $stack = array_map(static fn (int $id): array => ['id' => $id, 'root' => true], array_reverse($parentIds));

        while ($node = array_pop($stack)) {
            if (!$node['root']) {
                if (isset($seen[$node['id']])) {
                    continue;
                }

                $seen[$node['id']] = true;
                $ids[] = $node['id'];
            }

            foreach (array_reverse($children[$node['id']] ?? []) as $child) {
                $stack[] = ['id' => $child['id'], 'root' => false];
            }
        }

        return $ids;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<int>
     */
    private function sortParents(array $rows, int $id): array
    {
        $parents = [];

        foreach ($rows as $row) {
            $parents[(int) $row['id']] = (int) $row['pid'];
        }

        $ids = [];

        while (isset($parents[$id]) && !\in_array($id, $ids, true)) {
            $ids[] = $id;
            $id = $parents[$id];
        }

        return $ids;
    }

    private function getParentTableCondition(string $table, string $alias = ''): string
    {
        if (!isset($this->hasParentTableColumn[$table])) {
            $this->hasParentTableColumn[$table] = $this->connection->createSchemaManager()->introspectTable($table)->hasColumn('ptable');
        }

        $quotedTable = $this->connection->getDatabasePlatform()->quoteStringLiteral($table);

        return $this->hasParentTableColumn[$table] ? " AND {$alias}ptable = $quotedTable" : '';
    }

    private function getWhereCondition(ChildQuery $query): string
    {
        return $query->where() ? ' AND ('.$query->where().')' : '';
    }

    private function supportsRecursiveCommonTableExpressions(): bool
    {
        $platform = $this->connection->getDatabasePlatform();

        return $platform instanceof MariaDBPlatform || $platform instanceof MySQL80Platform;
    }

    /**
     * @param list<int|string> $ids
     *
     * @return list<int>
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map(intval(...), $ids))));
    }
}
