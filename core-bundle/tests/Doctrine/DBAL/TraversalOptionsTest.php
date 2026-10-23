<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\DBAL;

use Contao\CoreBundle\Doctrine\DBAL\AbstractTraversalOptions;
use Contao\CoreBundle\Doctrine\DBAL\ChildTraversalOptions;
use Contao\CoreBundle\Doctrine\DBAL\HierarchyDefinition;
use Contao\CoreBundle\Doctrine\DBAL\ParentTraversalOptions;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TraversalOptionsTest extends TestCase
{
    public function testReplacesScopesImmutably(): void
    {
        $definition = new HierarchyDefinition('categories', 'id', 'pid');
        $scoped = $definition->withScopes(['tree_type' => 'category', 'tenant_id' => 7]);

        $this->assertSame([], $definition->scopes());
        $this->assertSame(['tree_type' => 'category', 'tenant_id' => 7], $scoped->scopes());
        $this->assertSame(['tenant_id' => 8], $scoped->withScopes(['tenant_id' => 8])->scopes());
        $this->assertSame([], $scoped->withScopes([])->scopes());
        $this->assertSame(['tree_type' => 'category', 'tenant_id' => 7], $scoped->scopes());
    }

    public function testReplacesWhereBindingsImmutably(): void
    {
        $options = new ChildTraversalOptions();
        $filtered = $options->withWhere('published = ?', [true], [ParameterType::BOOLEAN]);
        $replaced = $filtered->withWhere('tstamp > 0');

        $this->assertSame('', $options->where());
        $this->assertSame([], $options->whereParameters());
        $this->assertSame([], $options->whereTypes());
        $this->assertSame('published = ?', $filtered->where());
        $this->assertSame([true], $filtered->whereParameters());
        $this->assertSame([ParameterType::BOOLEAN], $filtered->whereTypes());
        $this->assertSame('tstamp > 0', $replaced->where());
        $this->assertSame([], $replaced->whereParameters());
        $this->assertSame([], $replaced->whereTypes());
    }

    #[DataProvider('optionsProvider')]
    public function testConfiguresSharedOptionsImmutably(AbstractTraversalOptions $options): void
    {
        $configured = $options->withColumns('title', 'title', 'published')->withMaxDepth(2);

        $this->assertNotSame($options, $configured);
        $this->assertSame([], $options->columns());
        $this->assertFalse($options->includesAllColumns());
        $this->assertNull($options->maxDepth());
        $this->assertSame(['title', 'published'], $configured->columns());
        $this->assertSame(2, $configured->maxDepth());

        $allColumns = $configured->withAllColumns();

        $this->assertSame([], $allColumns->columns());
        $this->assertTrue($allColumns->includesAllColumns());
        $this->assertFalse($allColumns->withColumns('title')->includesAllColumns());
    }

    public function testRejectsAnInvalidMaximumDepth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The maximum depth must be greater than zero.');

        new ChildTraversalOptions()->withMaxDepth(0);
    }

    public static function optionsProvider(): iterable
    {
        yield 'children' => [new ChildTraversalOptions()];
        yield 'parents' => [new ParentTraversalOptions()];
    }
}
