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
     * @param int|string|list<int|string> $parentIds
     *
     * @return list<int|string>
     */
    public function getChildIds(array|int|string $parentIds, HierarchyDefinition $definition, ChildQuery|null $query = null): array
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
            return array_map(fn (array $row): int|string => $this->getRowId($row, 'node_id'), $rows);
        }

        return $this->sortChildren($rows, $parentIds);
    }

    /**
     * @return list<int|string>
     */
    public function getParentIds(int|string $id, HierarchyDefinition $definition, bool $skipId = false): array
    {
        if ('' === $id) {
            return [];
        }

        if ($this->supportsRecursiveCommonTableExpressions()) {
            $ids = $this->sortParents($this->fetchParentsUsingCommonTableExpression($id, $definition), $id);
        } else {
            $ids = $this->fetchParentIdsUsingUnion($id, $definition);
        }

        return $skipId ? array_values(array_filter($ids, static fn (int|string $parentId): bool => $parentId !== $id)) : $ids;
    }

    /**
     * @param list<int|string> $parentIds
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

        return $this->connection->fetchAllAssociative($sql, [$parentIds], [$this->getArrayParameterType($parentIds)]);
    }

    /**
     * @param list<int|string> $parentIds
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
                [$this->getArrayParameterType($pendingIds)],
            );
            $pendingIds = [];

            foreach ($result as $row) {
                $id = $this->getRowId($row, 'node_id');
                $key = $this->getIdKey($id);

                if (isset($rows[$key])) {
                    continue;
                }

                $rows[$key] = $row;
                $pendingIds[] = $id;
            }
        }

        return array_values($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchParentsUsingCommonTableExpression(int|string $id, HierarchyDefinition $definition): array
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
     * @return list<int|string>
     */
    private function fetchParentIdsUsingUnion(int|string $id, HierarchyDefinition $definition): array
    {
        $table = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $scopeCondition = $this->getScopeCondition($definition);
        $query = "SELECT $idColumn, @parent_id := $parentColumn FROM $table WHERE $idColumn = ?$scopeCondition".str_repeat(" UNION SELECT $idColumn, @parent_id := $parentColumn FROM $table WHERE $idColumn = @parent_id$scopeCondition", 9);
        $ids = [];
        $seen = [];
        $queried = [];
        $currentId = $id;

        while (!isset($queried[$this->getIdKey($currentId)])) {
            $queried[$this->getIdKey($currentId)] = true;
            $batch = $this->normalizeFetchedIds($this->connection->fetchFirstColumn($query, [$currentId]));

            if ([] === $batch) {
                break;
            }

            foreach ($batch as $parentId) {
                $key = $this->getIdKey($parentId);

                if (!isset($seen[$key])) {
                    $seen[$key] = true;
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
     * @param list<int|string>           $parentIds
     *
     * @return list<int|string>
     */
    private function sortChildren(array $rows, array $parentIds): array
    {
        $children = [];

        foreach ($rows as $row) {
            $parentId = $this->getRowId($row, 'parent_id');
            $children[$this->getIdKey($parentId)][] = ['id' => $this->getRowId($row, 'node_id'), 'order' => $row['order_value']];
        }

        foreach ($children as &$siblings) {
            usort($siblings, static fn (array $a, array $b): int => [$a['order'], $a['id']] <=> [$b['order'], $b['id']]);
        }
        unset($siblings);

        return $this->sortChildrenDepthFirst($children, $parentIds);
    }

    /**
     * @param array<string, list<array{id: int|string, order: mixed}>> $children
     * @param list<int|string>                                         $parentIds
     *
     * @return list<int|string>
     */
    private function sortChildrenDepthFirst(array $children, array $parentIds): array
    {
        $ids = [];
        $seen = [];
        $stack = array_map(static fn (int|string $id): array => ['id' => $id, 'root' => true], array_reverse($parentIds));

        while ($node = array_pop($stack)) {
            if (!$node['root']) {
                $key = $this->getIdKey($node['id']);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $ids[] = $node['id'];
            }

            foreach (array_reverse($children[$this->getIdKey($node['id'])] ?? []) as $child) {
                $stack[] = ['id' => $child['id'], 'root' => false];
            }
        }

        return $ids;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<int|string>
     */
    private function sortParents(array $rows, int|string $id): array
    {
        $parents = [];

        foreach ($rows as $row) {
            $parents[$this->getIdKey($this->getRowId($row, 'node_id'))] = $row;
        }

        $ids = [];
        $seen = [];

        while (isset($parents[$this->getIdKey($id)]) && !isset($seen[$this->getIdKey($id)])) {
            $row = $parents[$this->getIdKey($id)];
            $id = $this->getRowId($row, 'node_id');
            $seen[$this->getIdKey($id)] = true;
            $ids[] = $id;
            $id = $this->getRowId($row, 'parent_id');
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
     * @return list<int|string>
     */
    private function normalizeIds(array $ids): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            if ('' !== $id) {
                $normalized[$this->getIdKey($id)] = $id;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param non-empty-list<int|string> $ids
     */
    private function getArrayParameterType(array $ids): ArrayParameterType
    {
        return array_all($ids, static fn (mixed $id): bool => \is_int($id)) ? ArrayParameterType::INTEGER : ArrayParameterType::STRING;
    }

    private function getIdKey(int|string $id): string
    {
        return (\is_int($id) ? 'i:' : 's:').$id;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function getRowId(array $row, string $column): int|string
    {
        return $this->normalizeIdentifier($row[$column] ?? null, $column);
    }

    private function normalizeIdentifier(mixed $id, string $source): int|string
    {
        if (!\is_int($id) && !\is_string($id)) {
            throw new \UnexpectedValueException(\sprintf('The hierarchy identifier from "%s" must be an integer or a string.', $source));
        }

        return $id;
    }

    /**
     * @param list<mixed> $ids
     *
     * @return list<int|string>
     */
    private function normalizeFetchedIds(array $ids): array
    {
        return array_map(fn (mixed $id): int|string => $this->normalizeIdentifier($id, 'query result'), $ids);
    }
}
