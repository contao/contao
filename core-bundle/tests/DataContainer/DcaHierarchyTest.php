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
            ->method('getChildIds')
            ->with([1, 2], $this->isDcaDefinition('tl_page'), $query)
            ->willReturn([3, 4])
        ;

        $this->assertSame([3, 4], new DcaHierarchy($hierarchy)->getChildIds([1, 2], 'tl_page', $query));
    }

    public function testGetsParentIds(): void
    {
        $hierarchy = $this->createMock(Hierarchy::class);
        $hierarchy
            ->expects($this->once())
            ->method('getParentIds')
            ->with(
                5,
                $this->isDcaDefinition('tl_page'),
                true,
            )
            ->willReturn([3, 1])
        ;

        $this->assertSame([3, 1], new DcaHierarchy($hierarchy)->getParentIds(5, 'tl_page', true));
    }

    public function testIgnoresZeroChildIds(): void
    {
        $hierarchy = $this->createMock(Hierarchy::class);
        $hierarchy
            ->expects($this->never())
            ->method('getChildIds')
        ;

        $this->assertSame([], new DcaHierarchy($hierarchy)->getChildIds([0, '0'], 'tl_page'));
    }

    public function testIgnoresZeroParentId(): void
    {
        $hierarchy = $this->createMock(Hierarchy::class);
        $hierarchy
            ->expects($this->never())
            ->method('getParentIds')
        ;

        $this->assertSame([], new DcaHierarchy($hierarchy)->getParentIds(0, 'tl_page'));
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
