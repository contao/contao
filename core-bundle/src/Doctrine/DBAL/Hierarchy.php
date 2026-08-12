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
    private array $hasScopeColumn = [];

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @param int|list<int|string> $parentIds
     *
     * @return list<int>
     */
    public function getChildIds(array|int $parentIds, HierarchyDefinition $definition, ChildQuery|null $query = null): array
    {
        $parentIds = $this->normalizeIds((array) $parentIds);
        $query ??= new ChildQuery();

        if ([] === $parentIds) {
            return [];
        }

        $rows = $this->supportsRecursiveCommonTableExpressions()
            ? $this->fetchChildrenUsingCommonTableExpression($parentIds, $definition, $query)
            : $this->fetchChildrenIteratively($parentIds, $definition, $query);

        if (null === $query->orderBy()) {
            return array_map(static fn (array $row): int => (int) $row['node_id'], $rows);
        }

        return $this->sortChildren($rows, $parentIds);
    }

    /**
     * @return list<int>
     */
    public function getParentIds(int $id, HierarchyDefinition $definition, bool $skipId = false): array
    {
        if ($id <= 0) {
            return [];
        }

        if ($this->supportsRecursiveCommonTableExpressions()) {
            $ids = $this->sortParents($this->fetchParentsUsingCommonTableExpression($id, $definition), $id);
        } else {
            $ids = $this->fetchParentIdsUsingUnion($id, $definition);
        }

        return $skipId ? array_values(array_diff($ids, [$id])) : $ids;
    }

    /**
     * @param list<int> $parentIds
     *
     * @return list<array<string, mixed>>
     */
    private function fetchChildrenUsingCommonTableExpression(array $parentIds, HierarchyDefinition $definition, ChildQuery $query): array
    {
        $quotedTable = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $orderBy = $this->getOrderBySelect($query);
        $scopeCondition = $this->getScopeCondition($definition);
        $where = $this->getWhereCondition($query);

        $sql = <<<SQL
            WITH RECURSIVE contao_tree (node_id, parent_id, order_value) AS (
                SELECT $idColumn, $parentColumn, $orderBy FROM $quotedTable WHERE $parentColumn IN (?)$scopeCondition$where
                UNION DISTINCT
                SELECT child.node_id, child.parent_id, child.order_value FROM (
                    SELECT $idColumn AS node_id, $parentColumn AS parent_id, $orderBy FROM $quotedTable WHERE 1 = 1$scopeCondition$where
                ) child
                    INNER JOIN contao_tree parent ON child.parent_id = parent.node_id
            )
            SELECT node_id, parent_id, order_value FROM contao_tree
            SQL;

        return $this->connection->fetchAllAssociative($sql, [$parentIds], [ArrayParameterType::INTEGER]);
    }

    /**
     * @param list<int> $parentIds
     *
     * @return list<array<string, mixed>>
     */
    private function fetchChildrenIteratively(array $parentIds, HierarchyDefinition $definition, ChildQuery $query): array
    {
        $quotedTable = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $orderBy = $this->getOrderBySelect($query);
        $scopeCondition = $this->getScopeCondition($definition);
        $where = $this->getWhereCondition($query);
        $rows = [];
        $pendingIds = $parentIds;

        while ([] !== $pendingIds) {
            $result = $this->connection->fetchAllAssociative(
                "SELECT $idColumn AS node_id, $parentColumn AS parent_id, $orderBy FROM $quotedTable WHERE $parentColumn IN (?)$scopeCondition$where",
                [$pendingIds],
                [ArrayParameterType::INTEGER],
            );
            $pendingIds = [];

            foreach ($result as $row) {
                $id = (int) $row['node_id'];

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
    private function fetchParentsUsingCommonTableExpression(int $id, HierarchyDefinition $definition): array
    {
        $table = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $scopeCondition = $this->getScopeCondition($definition);
        $sql = <<<SQL
            WITH RECURSIVE contao_tree (node_id, parent_id) AS (
                SELECT $idColumn, $parentColumn FROM $table WHERE $idColumn = ?$scopeCondition
                UNION DISTINCT
                SELECT parent.$idColumn, parent.$parentColumn FROM $table parent
                    INNER JOIN contao_tree child ON parent.$idColumn = child.parent_id
                    WHERE 1 = 1$scopeCondition
            )
            SELECT node_id, parent_id FROM contao_tree
            SQL;

        return $this->connection->fetchAllAssociative($sql, [$id]);
    }

    /**
     * @return list<int>
     */
    private function fetchParentIdsUsingUnion(int $id, HierarchyDefinition $definition): array
    {
        $table = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $scopeCondition = $this->getScopeCondition($definition);
        $query = "SELECT $idColumn, @parent_id := $parentColumn FROM $table WHERE $idColumn = ?$scopeCondition".str_repeat(" UNION SELECT $idColumn, @parent_id := $parentColumn FROM $table WHERE $idColumn = @parent_id$scopeCondition", 9);
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
    private function sortChildren(array $rows, array $parentIds): array
    {
        $children = [];

        foreach ($rows as $row) {
            $children[(int) $row['parent_id']][] = ['id' => (int) $row['node_id'], 'order' => $row['order_value']];
        }

        foreach ($children as &$siblings) {
            usort($siblings, static fn (array $a, array $b): int => [$a['order'], $a['id']] <=> [$b['order'], $b['id']]);
        }
        unset($siblings);

        return $this->sortChildrenDepthFirst($children, $parentIds);
    }

    /**
     * @param array<int, list<array{id: int, order: mixed}>> $children
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
            $parents[(int) $row['node_id']] = (int) $row['parent_id'];
        }

        $ids = [];

        while (isset($parents[$id]) && !\in_array($id, $ids, true)) {
            $ids[] = $id;
            $id = $parents[$id];
        }

        return $ids;
    }

    private function getScopeCondition(HierarchyDefinition $definition): string
    {
        if (null === $definition->scopeColumn()) {
            return '';
        }

        if ($definition->hasOptionalScope() && !$this->hasScopeColumn($definition)) {
            return '';
        }

        $column = $this->connection->quoteIdentifier($definition->scopeColumn());
        $value = $definition->scopeValue();
        $value = \is_int($value) ? (string) $value : $this->connection->getDatabasePlatform()->quoteStringLiteral($value);

        return " AND $column = $value";
    }

    private function hasScopeColumn(HierarchyDefinition $definition): bool
    {
        $key = $definition->table().':'.$definition->scopeColumn();

        return $this->hasScopeColumn[$key] ??= $this->connection
            ->createSchemaManager()
            ->introspectTable($definition->table())
            ->hasColumn($definition->scopeColumn())
        ;
    }

    private function getWhereCondition(ChildQuery $query): string
    {
        return $query->where() ? ' AND ('.$query->where().')' : '';
    }

    private function getOrderBySelect(ChildQuery $query): string
    {
        $orderBy = $query->orderBy();

        return null === $orderBy ? '0 AS order_value' : $this->connection->quoteIdentifier($orderBy).' AS order_value';
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
