<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\Doctrine\DBAL\ChildQuery;
use Contao\CoreBundle\Doctrine\DBAL\Hierarchy;
use Contao\CoreBundle\Doctrine\DBAL\HierarchyDefinition;
use Contao\CoreBundle\Doctrine\DBAL\ParentQuery;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\TestCase;

class DcaHierarchyTest extends TestCase
{
    public function testGetsChildIds(): void
    {
        $query = new ChildQuery();
        $hierarchy = $this->createMock(Hierarchy::class);
        $hierarchy
            ->expects($this->once())
            ->method('getChildRows')
            ->with([1, 2], $this->isDcaDefinition('tl_page'), $query)
            ->willReturn([
                ['id' => 3, 'pid' => 1],
                ['id' => 4, 'pid' => 2],
            ])
        ;

        $this->assertSame([3, 4], new DcaHierarchy($hierarchy)->getChildIds([1, 2], 'tl_page', $query));
    }

    public function testGetsParentIds(): void
    {
        $hierarchy = $this->createMock(Hierarchy::class);
        $hierarchy
            ->expects($this->once())
            ->method('getParentRows')
            ->with(
                5,
                $this->isDcaDefinition('tl_page'),
                null,
            )
            ->willReturn([
                ['id' => 5, 'pid' => 3],
                ['id' => 3, 'pid' => 1],
                ['id' => 1, 'pid' => 0],
            ])
        ;

        $this->assertSame([3, 1], new DcaHierarchy($hierarchy)->getParentIds(5, 'tl_page', true));
    }

    public function testIgnoresZeroChildIds(): void
    {
        $hierarchy = $this->createMock(Hierarchy::class);
        $hierarchy
            ->expects($this->never())
            ->method('getChildRows')
        ;

        $this->assertSame([], new DcaHierarchy($hierarchy)->getChildIds([0, '0'], 'tl_page'));
    }

    public function testIgnoresZeroParentId(): void
    {
        $hierarchy = $this->createMock(Hierarchy::class);
        $hierarchy
            ->expects($this->never())
            ->method('getParentRows')
        ;

        $this->assertSame([], new DcaHierarchy($hierarchy)->getParentIds(0, 'tl_page'));
    }

    public function testGetsParentTableAndId(): void
    {
        $hierarchy = $this->createMock(Hierarchy::class);
        $hierarchy
            ->expects($this->once())
            ->method('getParentRows')
            ->with(
                5,
                $this->isDcaDefinition('tl_content'),
                $this->callback(static fn (ParentQuery $query): bool => ['ptable'] === $query->columns() && $query->includesBoundaryRow()),
            )
            ->willReturn([
                ['id' => 5, 'pid' => 3, 'ptable' => 'tl_content'],
                ['id' => 3, 'pid' => 10, 'ptable' => 'tl_article'],
            ])
        ;

        $this->assertSame(['tl_article', 10], new DcaHierarchy($hierarchy)->getParentTableAndId(5, 'tl_content'));
    }

    public function testGetsRowsWithAdditionalColumns(): void
    {
        $childQuery = new ChildQuery()->withColumns('title');
        $parentQuery = new ParentQuery()->withColumns('title');
        $hierarchy = $this->createMock(Hierarchy::class);
        $hierarchy
            ->expects($this->once())
            ->method('getChildRows')
            ->with([1], $this->isDcaDefinition('tl_page'), $childQuery)
            ->willReturn([['id' => '2', 'pid' => '1', 'title' => 'Child']])
        ;

        $hierarchy
            ->expects($this->once())
            ->method('getParentRows')
            ->with(
                2,
                $this->isDcaDefinition('tl_page'),
                $parentQuery,
            )
            ->willReturn([['id' => '2', 'pid' => '1', 'title' => 'Child']])
        ;
        $dcaHierarchy = new DcaHierarchy($hierarchy);
        $expected = [['id' => 2, 'pid' => 1, 'title' => 'Child']];

        $this->assertSame($expected, $dcaHierarchy->getChildRows(1, 'tl_page', $childQuery));
        $this->assertSame($expected, $dcaHierarchy->getParentRows(2, 'tl_page', $parentQuery));
    }

    /**
     * @return Callback<HierarchyDefinition>
     */
    private function isDcaDefinition(string $table): Callback
    {
        return $this->callback(static fn (HierarchyDefinition $definition): bool => $table === $definition->table()
            && 'id' === $definition->idColumn()
            && 'pid' === $definition->parentColumn()
            && 'ptable' === $definition->scopeColumn()
            && $table === $definition->scopeValue()
            && $definition->hasOptionalScope());
    }
}
