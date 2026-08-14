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
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @param int|string|list<int|string> $parentIds
     *
     * @return list<int|string>
     */
    public function getChildIds(array|int|string $parentIds, HierarchyDefinition $definition, ChildTraversalOptions|null $options = null): array
    {
        return array_map(
            static fn (array $row): int|string => $row[$definition->idColumn()],
            $this->getChildRows($parentIds, $definition, $options),
        );
    }

    /**
     * @param int|string|list<int|string> $parentIds
     *
     * @return list<array<string, mixed>>
     */
    public function getChildRows(array|int|string $parentIds, HierarchyDefinition $definition, ChildTraversalOptions|null $options = null): array
    {
        $parentIds = $this->normalizeIds((array) $parentIds);
        $options ??= new ChildTraversalOptions();

        if ([] === $parentIds) {
            return [];
        }

        $rows = $this->supportsRecursiveCommonTableExpressions()
            ? $this->fetchChildrenUsingCommonTableExpression($parentIds, $definition, $options)
            : $this->fetchChildrenIteratively($parentIds, $definition, $options);

        if (null !== $options->orderBy()) {
            $rows = $this->sortChildRows($rows, $parentIds);
        }

        return $this->mapRows($rows, $definition, $options);
    }

    /**
     * @param int|string|list<int|string> $ids
     *
     * @return list<int|string>
     */
    public function getParentIds(array|int|string $ids, HierarchyDefinition $definition, bool $skipIds = false): array
    {
        $parentIds = array_map(
            static fn (array $row): int|string => $row[$definition->idColumn()],
            $this->getParentRows($ids, $definition),
        );

        if (!$skipIds) {
            return $parentIds;
        }

        $rootIds = array_fill_keys(array_map($this->getIdKey(...), (array) $ids), true);

        return array_values(array_filter($parentIds, fn (int|string $parentId): bool => !isset($rootIds[$this->getIdKey($parentId)])));
    }

    /**
     * Returns one parent ID trail for each given ID, in the same order as the IDs.
     *
     * @param list<int|string> $ids
     *
     * @return list<list<int|string>>
     */
    public function getParentIdTrails(array $ids, HierarchyDefinition $definition, bool $skipIds = false): array
    {
        $ids = $this->normalizeIds($ids);

        if ([] === $ids) {
            return [];
        }

        $rows = $this->getParentRows($ids, $definition);
        $parents = [];

        foreach ($rows as $row) {
            $parents[$this->getIdKey($this->getRowId($row, $definition->idColumn()))] = $this->getRowId($row, $definition->parentColumn());
        }

        return array_map(
            fn (int|string $id): array => $this->getParentIdTrail($id, $parents, $skipIds),
            $ids,
        );
    }

    /**
     * @param int|string|list<int|string> $ids
     *
     * @return list<array<string, mixed>>
     */
    public function getParentRows(array|int|string $ids, HierarchyDefinition $definition, ParentTraversalOptions|null $options = null): array
    {
        $ids = $this->normalizeIds((array) $ids);

        if ([] === $ids) {
            return [];
        }

        $options ??= new ParentTraversalOptions();
        $supportsCommonTableExpressions = $this->supportsRecursiveCommonTableExpressions();
        $rows = $supportsCommonTableExpressions
            ? $this->fetchParentRowsUsingCommonTableExpression($ids, $definition, $options)
            : $this->fetchParentRowsUsingUnion($ids, $definition, $options);

        if ($supportsCommonTableExpressions && $options->includesAllColumns()) {
            return $this->sortParentRows($rows, $ids, $definition);
        }

        return $this->mapRows($this->sortParentRows($rows, $ids), $definition, $options);
    }

    /**
     * @param list<int|string> $parentIds
     *
     * @return list<array<string, mixed>>
     */
    private function fetchChildrenUsingCommonTableExpression(array $parentIds, HierarchyDefinition $definition, ChildTraversalOptions $options): array
    {
        $quotedTable = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $orderBy = $this->getOrderBySelect($options);
        $fields = $this->getFields($options->columns());
        $fieldNames = $this->getFieldNames($options->columns());
        $childFieldNames = $this->getFieldNames($options->columns(), 'child');
        $scopeCondition = $this->getScopeCondition($definition);
        $where = $this->getWhereCondition($options);
        $depthColumn = null === $options->maxDepth() ? '' : ', depth';
        $anchorDepth = null === $options->maxDepth() ? '' : ', 1';
        $childDepth = null === $options->maxDepth() ? '' : ', parent.depth + 1';
        $recursiveDepth = null === $options->maxDepth() ? '' : ' AND parent.depth < '.$options->maxDepth();

        $sql = <<<SQL
            WITH RECURSIVE contao_tree (node_id, parent_id, order_value$fieldNames$depthColumn) AS (
                SELECT $idColumn, $parentColumn, $orderBy$fields$anchorDepth FROM $quotedTable WHERE $parentColumn IN (?)$scopeCondition$where
                UNION DISTINCT
                SELECT child.node_id, child.parent_id, child.order_value$childFieldNames$childDepth FROM (
                    SELECT $idColumn AS node_id, $parentColumn AS parent_id, $orderBy$fields FROM $quotedTable WHERE 1 = 1$scopeCondition$where
                ) child
                    INNER JOIN contao_tree parent ON child.parent_id = parent.node_id
                    WHERE 1 = 1$recursiveDepth
            )
            SELECT DISTINCT node_id, parent_id, order_value$fieldNames FROM contao_tree
            SQL;

        return $this->connection->fetchAllAssociative($sql, [$parentIds], [$this->getArrayParameterType($parentIds)]);
    }

    /**
     * @param list<int|string> $parentIds
     *
     * @return list<array<string, mixed>>
     */
    private function fetchChildrenIteratively(array $parentIds, HierarchyDefinition $definition, ChildTraversalOptions $options): array
    {
        $quotedTable = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $orderBy = $this->getOrderBySelect($options);
        $fields = $this->getFields($options->columns());
        $scopeCondition = $this->getScopeCondition($definition);
        $where = $this->getWhereCondition($options);
        $rows = [];
        $pendingIds = $parentIds;
        $depth = 1;

        while ([] !== $pendingIds && (null === $options->maxDepth() || $depth <= $options->maxDepth())) {
            $result = $this->connection->fetchAllAssociative(
                "SELECT $idColumn AS node_id, $parentColumn AS parent_id, $orderBy$fields FROM $quotedTable WHERE $parentColumn IN (?)$scopeCondition$where",
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

            ++$depth;
        }

        return array_values($rows);
    }

    /**
     * @param non-empty-list<int|string> $ids
     *
     * @return list<array<string, mixed>>
     */
    private function fetchParentRowsUsingCommonTableExpression(array $ids, HierarchyDefinition $definition, ParentTraversalOptions $options): array
    {
        $table = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $fields = $this->getFields($options->columns());
        $parentFields = $this->getFields($options->columns(), 'parent');
        $fieldNames = $this->getFieldNames($options->columns());
        $anchorScope = $options->includesBoundaryRow() ? '' : $this->getScopeCondition($definition);
        $recursiveScope = $options->includesBoundaryRow() ? ' AND child.continue_traversal = 1' : $this->getScopeCondition($definition, 'parent');
        $depthColumn = null === $options->maxDepth() ? '' : ', depth';
        $anchorDepth = null === $options->maxDepth() ? '' : ', 1';
        $parentDepth = null === $options->maxDepth() ? '' : ', child.depth + 1';
        $recursiveDepth = null === $options->maxDepth() ? '' : ' AND child.depth < '.$options->maxDepth();
        $continuation = $this->getScopeExpression($definition);
        $parentContinuation = $this->getScopeExpression($definition, 'parent');
        $select = $options->includesAllColumns()
            ? "SELECT source.* FROM contao_tree INNER JOIN $table source ON source.$idColumn = contao_tree.node_id"
            : "SELECT node_id, parent_id$fieldNames, continue_traversal FROM contao_tree";
        $anchor = 1 === \count($ids) ? "$idColumn = ?" : "$idColumn IN (?)";
        $sql = <<<SQL
            WITH RECURSIVE contao_tree (node_id, parent_id$fieldNames, continue_traversal$depthColumn) AS (
                SELECT $idColumn, $parentColumn$fields, $continuation$anchorDepth FROM $table WHERE $anchor$anchorScope
                UNION DISTINCT
                SELECT parent.$idColumn, parent.$parentColumn$parentFields, $parentContinuation$parentDepth FROM $table parent
                    INNER JOIN contao_tree child ON parent.$idColumn = child.parent_id
                    WHERE 1 = 1$recursiveScope$recursiveDepth
            )
            $select
            SQL;

        return 1 === \count($ids)
            ? $this->connection->fetchAllAssociative($sql, $ids)
            : $this->connection->fetchAllAssociative($sql, [$ids], [$this->getArrayParameterType($ids)]);
    }

    /**
     * @param non-empty-list<int|string> $ids
     *
     * @return list<array<string, mixed>>
     */
    private function fetchParentRowsUsingUnion(array $ids, HierarchyDefinition $definition, ParentTraversalOptions $options): array
    {
        $rows = [];

        foreach ($ids as $id) {
            foreach ($this->fetchSingleParentRowsUsingUnion($id, $definition, $options) as $row) {
                $rows[$this->getIdKey($this->getRowId($row, 'node_id'))] = $row;
            }
        }

        return array_values($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchSingleParentRowsUsingUnion(int|string $id, HierarchyDefinition $definition, ParentTraversalOptions $options): array
    {
        $rows = [];
        $queried = [];
        $currentId = $id;
        $fetched = 0;

        while (!isset($queried[$this->getIdKey($currentId)])) {
            $queried[$this->getIdKey($currentId)] = true;
            $batchSize = $this->getParentBatchSize($options, $fetched);
            $select = $this->getParentUnionSelect($definition, $options, true);
            $querySql = $select.str_repeat(' UNION '.$this->getParentUnionSelect($definition, $options, false), $batchSize - 1);
            $batch = $this->connection->fetchAllAssociative($querySql, [$currentId]);

            foreach ($batch as $row) {
                $rows[$this->getIdKey($this->getRowId($row, 'node_id'))] = $row;
            }

            $last = end($batch);
            $fetched += \count($batch) - (0 === $fetched ? 0 : 1);

            if ($batchSize !== \count($batch) || $fetched === $options->maxDepth() || ($options->includesBoundaryRow() && !(bool) ($last['continue_traversal'] ?? false))) {
                break;
            }

            $currentId = $this->getRowId($last, 'node_id');
        }

        return array_values($rows);
    }

    private function getParentBatchSize(ParentTraversalOptions $options, int $fetched): int
    {
        if (null === $options->maxDepth()) {
            return 10;
        }

        return min(10, $options->maxDepth() - $fetched + (0 === $fetched ? 0 : 1));
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

        return $this->sortChildrenDepthFirst($children, $this->getRootParentIds($children, $parentIds));
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<int|string>           $parentIds
     *
     * @return list<array<string, mixed>>
     */
    private function sortChildRows(array $rows, array $parentIds): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            $indexed[$this->getIdKey($this->getRowId($row, 'node_id'))] = $row;
        }

        return array_map(fn (int|string $id): array => $indexed[$this->getIdKey($id)], $this->sortChildren($rows, $parentIds));
    }

    /**
     * @param array<string, list<array{id: int|string, order: mixed}>> $children
     * @param list<int|string>                                         $parentIds
     *
     * @return list<int|string>
     */
    private function getRootParentIds(array $children, array $parentIds): array
    {
        $parentKeys = array_fill_keys(array_map($this->getIdKey(...), $parentIds), true);
        $nestedKeys = [];

        foreach ($parentIds as $parentId) {
            $stack = $children[$this->getIdKey($parentId)] ?? [];
            $seen = [];

            while ($child = array_pop($stack)) {
                $key = $this->getIdKey($child['id']);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;

                if (isset($parentKeys[$key]) && $child['id'] !== $parentId) {
                    $nestedKeys[$key] = true;
                }

                array_push($stack, ...($children[$key] ?? []));
            }
        }

        $rootIds = array_values(array_filter($parentIds, fn (int|string $id): bool => !isset($nestedKeys[$this->getIdKey($id)])));

        return [] === $rootIds ? [$parentIds[0]] : $rootIds;
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
     * @param non-empty-list<int|string> $ids
     *
     * @return list<array<string, mixed>>
     */
    private function sortParentRows(array $rows, array $ids, HierarchyDefinition|null $definition = null): array
    {
        $idColumn = $definition?->idColumn() ?? 'node_id';
        $parentColumn = $definition?->parentColumn() ?? 'parent_id';
        $parents = [];

        foreach ($rows as $row) {
            $parents[$this->getIdKey($this->getRowId($row, $idColumn))] = $row;
        }

        $sorted = [];
        $seen = [];

        foreach ($ids as $id) {
            while (isset($parents[$this->getIdKey($id)]) && !isset($seen[$this->getIdKey($id)])) {
                $row = $parents[$this->getIdKey($id)];
                $seen[$this->getIdKey($id)] = true;
                $sorted[] = $row;
                $id = $this->getRowId($row, $parentColumn);
            }
        }

        return $sorted;
    }

    /**
     * @param array<string, int|string> $parents
     *
     * @return list<int|string>
     */
    private function getParentIdTrail(int|string $id, array $parents, bool $skipId): array
    {
        $trail = [];
        $seen = [];

        while (isset($parents[$key = $this->getIdKey($id)]) && !isset($seen[$key])) {
            $seen[$key] = true;

            if (!$skipId) {
                $trail[] = $id;
            }

            $skipId = false;
            $id = $parents[$key];
        }

        return $trail;
    }

    /**
     * @param list<string> $columns
     */
    private function getFields(array $columns, string|null $alias = null): string
    {
        $prefix = null === $alias ? '' : $alias.'.';
        $fields = [];

        foreach ($columns as $index => $column) {
            $fields[] = $prefix.$this->connection->quoteIdentifier($column).' AS field_'.$index;
        }

        return $fields ? ', '.implode(', ', $fields) : '';
    }

    /**
     * @param list<string> $columns
     */
    private function getFieldNames(array $columns, string|null $alias = null): string
    {
        $prefix = null === $alias ? '' : $alias.'.';

        return implode('', array_map(static fn (int $index): string => ', '.$prefix.'field_'.$index, array_keys($columns)));
    }

    private function getParentUnionSelect(HierarchyDefinition $definition, ParentTraversalOptions $options, bool $anchor): string
    {
        $table = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $fields = $this->getFields($options->columns());
        $scope = $options->includesBoundaryRow() ? '' : $this->getScopeCondition($definition);
        $continuation = $this->getScopeExpression($definition);
        $where = $anchor ? "$idColumn = ?$scope" : "$idColumn = @parent_id".($options->includesBoundaryRow() ? ' AND @continue' : $scope);

        return "SELECT $idColumn AS node_id, @parent_id := $parentColumn AS parent_id$fields, @continue := $continuation AS continue_traversal FROM $table WHERE $where";
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function mapRow(array $row, HierarchyDefinition $definition, array $columns): array
    {
        $mapped = [
            $definition->idColumn() => $this->getRowId($row, 'node_id'),
            $definition->parentColumn() => $this->getRowId($row, 'parent_id'),
        ];

        foreach ($columns as $index => $column) {
            $mapped[$column] = $row['field_'.$index] ?? null;
        }

        return $mapped;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function mapRows(array $rows, HierarchyDefinition $definition, AbstractTraversalOptions $options): array
    {
        if ($options->includesAllColumns()) {
            return $this->fetchAllColumns($rows, $definition);
        }

        return array_map(fn (array $row): array => $this->mapRow($row, $definition, $options->columns()), $rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function fetchAllColumns(array $rows, HierarchyDefinition $definition): array
    {
        if ([] === $rows) {
            return [];
        }

        $ids = array_map(fn (array $row): int|string => $this->getRowId($row, 'node_id'), $rows);
        $table = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $fetchedRows = $this->connection->fetchAllAssociative(
            "SELECT * FROM $table WHERE $idColumn IN (?)",
            [$ids],
            [$this->getArrayParameterType($ids)],
        );
        $indexed = [];

        foreach ($fetchedRows as $row) {
            $indexed[$this->getIdKey($this->getRowId($row, $definition->idColumn()))] = $row;
        }

        return array_values(array_filter(array_map(
            fn (int|string $id): array|null => $indexed[$this->getIdKey($id)] ?? null,
            $ids,
        )));
    }

    private function getScopeCondition(HierarchyDefinition $definition, string|null $alias = null): string
    {
        if (null === $definition->scopeColumn()) {
            return '';
        }

        $column = (null === $alias ? '' : $alias.'.').$this->connection->quoteIdentifier($definition->scopeColumn());
        $value = $definition->scopeValue();
        $value = \is_int($value) ? (string) $value : $this->connection->getDatabasePlatform()->quoteStringLiteral($value);

        return " AND $column = $value";
    }

    private function getScopeExpression(HierarchyDefinition $definition, string|null $alias = null): string
    {
        $condition = $this->getScopeCondition($definition, $alias);

        return '' === $condition ? '1' : substr($condition, 5);
    }

    private function getWhereCondition(ChildTraversalOptions $options): string
    {
        return $options->where() ? ' AND ('.$options->where().')' : '';
    }

    private function getOrderBySelect(ChildTraversalOptions $options): string
    {
        $orderBy = $options->orderBy();

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
}
