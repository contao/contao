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
        $parentIds = array_values(array_filter(array_map(intval(...), (array) $parentIds)));

        if ([] === $parentIds) {
            return [];
        }

        return array_map(intval(...), $this->hierarchy->getChildIds($parentIds, $this->createDefinition($table), $query));
    }

    /**
     * @return list<int>
     */
    public function getParentIds(int $id, string $table, bool $skipId = false): array
    {
        if ($id <= 0) {
            return [];
        }

        return array_map(intval(...), $this->hierarchy->getParentIds($id, $this->createDefinition($table), $skipId));
    }

    private function createDefinition(string $table): HierarchyDefinition
    {
        return new HierarchyDefinition($table, 'id', 'pid')->withOptionalScope('ptable', $table);
    }
}
