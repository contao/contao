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

        return array_map(fn (array $row): array => $this->mapRow($row, $definition, $options->columns()), $rows);
    }

    /**
     * @return list<int|string>
     */
    public function getParentIds(int|string $id, HierarchyDefinition $definition, bool $skipId = false): array
    {
        $ids = array_map(
            static fn (array $row): int|string => $row[$definition->idColumn()],
            $this->getParentRows($id, $definition),
        );

        return $skipId ? array_values(array_filter($ids, static fn (int|string $parentId): bool => $parentId !== $id)) : $ids;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getParentRows(int|string $id, HierarchyDefinition $definition, ParentTraversalOptions|null $options = null): array
    {
        if ('' === $id) {
            return [];
        }

        $options ??= new ParentTraversalOptions();
        $rows = $this->supportsRecursiveCommonTableExpressions()
            ? $this->fetchParentRowsUsingCommonTableExpression($id, $definition, $options)
            : $this->fetchParentRowsUsingUnion($id, $definition, $options);

        return array_map(
            fn (array $row): array => $this->mapRow($row, $definition, $options->columns()),
            $this->sortParentRows($rows, $id),
        );
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
     * @return list<array<string, mixed>>
     */
    private function fetchParentRowsUsingCommonTableExpression(int|string $id, HierarchyDefinition $definition, ParentTraversalOptions $options): array
    {
        $table = $this->connection->quoteIdentifier($definition->table());
        $idColumn = $this->connection->quoteIdentifier($definition->idColumn());
        $parentColumn = $this->connection->quoteIdentifier($definition->parentColumn());
        $fields = $this->getFields($options->columns());
        $parentFields = $this->getFields($options->columns(), 'parent');
        $fieldNames = $this->getFieldNames($options->columns());
        $anchorScope = $options->includesBoundaryRow() ? '' : $this->getScopeCondition($definition);
        $recursiveScope = $options->includesBoundaryRow() ? ' AND child.continue_traversal = 1' : $this->getScopeCondition($definition, 'parent');
        $recursiveDepth = null === $options->maxDepth() ? '' : ' AND child.depth < '.$options->maxDepth();
        $continuation = $this->getScopeExpression($definition);
        $parentContinuation = $this->getScopeExpression($definition, 'parent');
        $sql = <<<SQL
            WITH RECURSIVE contao_tree (node_id, parent_id$fieldNames, continue_traversal, depth) AS (
                SELECT $idColumn, $parentColumn$fields, $continuation, 1 FROM $table WHERE $idColumn = ?$anchorScope
                UNION DISTINCT
                SELECT parent.$idColumn, parent.$parentColumn$parentFields, $parentContinuation, child.depth + 1 FROM $table parent
                    INNER JOIN contao_tree child ON parent.$idColumn = child.parent_id
                    WHERE 1 = 1$recursiveScope$recursiveDepth
            )
            SELECT node_id, parent_id$fieldNames, continue_traversal FROM contao_tree
            SQL;

        return $this->connection->fetchAllAssociative($sql, [$id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchParentRowsUsingUnion(int|string $id, HierarchyDefinition $definition, ParentTraversalOptions $options): array
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
     *
     * @return list<array<string, mixed>>
     */
    private function sortParentRows(array $rows, int|string $id): array
    {
        $parents = [];

        foreach ($rows as $row) {
            $parents[$this->getIdKey($this->getRowId($row, 'node_id'))] = $row;
        }

        $sorted = [];
        $seen = [];

        while (isset($parents[$this->getIdKey($id)]) && !isset($seen[$this->getIdKey($id)])) {
            $row = $parents[$this->getIdKey($id)];
            $seen[$this->getIdKey($id)] = true;
            $sorted[] = $row;
            $id = $this->getRowId($row, 'parent_id');
        }

        return $sorted;
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

    private function getScopeCondition(HierarchyDefinition $definition, string|null $alias = null): string
    {
        if (null === $definition->scopeColumn()) {
            return '';
        }

        if ($definition->hasOptionalScope() && !$this->hasScopeColumn($definition)) {
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

    private function hasScopeColumn(HierarchyDefinition $definition): bool
    {
        $key = $definition->table().':'.$definition->scopeColumn();

        return $this->hasScopeColumn[$key] ??= $this->connection
            ->createSchemaManager()
            ->introspectTable($definition->table())
            ->hasColumn($definition->scopeColumn())
        ;
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
