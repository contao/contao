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

use Contao\CoreBundle\Doctrine\DBAL\ChildTraversalOptions;
use Contao\CoreBundle\Doctrine\DBAL\Hierarchy;
use Contao\CoreBundle\Doctrine\DBAL\HierarchyDefinition;
use Contao\CoreBundle\Doctrine\DBAL\ParentTraversalOptions;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DcaLoader;

class DcaHierarchy
{
    public function __construct(
        private readonly Hierarchy $hierarchy,
        private readonly ContaoFramework $framework,
    ) {
    }

    /**
     * @param int|string|list<int|string> $parentIds
     *
     * @return list<int>
     */
    public function getChildIds(array|int|string $parentIds, string $table, ChildTraversalOptions|null $options = null): array
    {
        return array_column($this->getChildRows($parentIds, $table, $options), 'id');
    }

    /**
     * @param int|string|list<int|string> $parentIds
     *
     * @return list<array<string, mixed>>
     */
    public function getChildRows(array|int|string $parentIds, string $table, ChildTraversalOptions|null $options = null): array
    {
        $parentIds = $this->normalizeIds($parentIds);

        if ([] === $parentIds) {
            return [];
        }

        return $this->normalizeRows($this->hierarchy->getChildRows($parentIds, $this->createDefinition($table), $options));
    }

    /**
     * @param int|string|list<int|string> $ids
     *
     * @return list<int>
     */
    public function getParentIds(array|int|string $ids, string $table, bool $skipIds = false): array
    {
        $ids = $this->normalizeIds($ids);

        if ([] === $ids) {
            return [];
        }

        $parentIds = array_column($this->getParentRows($ids, $table), 'id');

        return $skipIds ? array_values(array_diff($parentIds, $ids)) : $parentIds;
    }

    /**
     * Returns one parent ID trail for each given ID, in the same order as the IDs.
     *
     * @param list<int|string> $ids
     *
     * @return list<list<int>>
     */
    public function getParentIdTrails(array $ids, string $table, bool $skipIds = false): array
    {
        $ids = $this->normalizeIds($ids);

        if ([] === $ids) {
            return [];
        }

        return array_map(
            static fn (array $trail): array => array_map(intval(...), $trail),
            $this->hierarchy->getParentIdTrails($ids, $this->createDefinition($table), $skipIds),
        );
    }

    /**
     * @param int|string|list<int|string> $ids
     *
     * @return list<array<string, mixed>>
     */
    public function getParentRows(array|int|string $ids, string $table, ParentTraversalOptions|null $options = null): array
    {
        $ids = $this->normalizeIds($ids);

        if ([] === $ids) {
            return [];
        }

        return $this->normalizeRows($this->hierarchy->getParentRows($ids, $this->createDefinition($table), $options));
    }

    /**
     * @return array{0: string, 1: int}
     *
     * @throws \RuntimeException if a parent record is not found
     */
    public function getParentTableAndId(int $id, string $table): array
    {
        $options = new ParentTraversalOptions()->withColumns('ptable')->withBoundaryRow();
        $rows = $this->getParentRows($id, $table, $options);
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

    /**
     * @param int|string|list<int|string> $ids
     *
     * @return list<int>
     */
    private function normalizeIds(array|int|string $ids): array
    {
        return array_values(array_unique(array_filter(array_map(intval(...), (array) $ids))));
    }

    private function createDefinition(string $table): HierarchyDefinition
    {
        $this->framework->createInstance(DcaLoader::class, [$table])->load();
        $definition = new HierarchyDefinition($table, 'id', 'pid');

        if ($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable'] ?? false) {
            $definition = $definition->withScope('ptable', $table);
        }

        return $definition;
    }
}
