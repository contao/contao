<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Util\Database;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\Database\ForeignKeyExpression;

class ForeignKeyExpressionTest extends TestCase
{
    public function testConstructorSetsTableNameAndColumnExpression(): void
    {
        $expr = new ForeignKeyExpression('table', 'field');

        $this->assertSame('table', $expr->getTableName());
        $this->assertSame('field', $expr->getColumnExpression());
        $this->assertNull($expr->getColumnName());
        $this->assertNull($expr->getKey());

        $expr = $expr->withKey('key')
            ->withColumnName('other_column')
            ->withColumnExpression('other_column_expression')
            ->withTableName('other_table')
        ;

        $this->assertSame('other_table', $expr->getTableName());
        $this->assertSame('other_column_expression', $expr->getColumnExpression());
        $this->assertSame('other_column', $expr->getColumnName());
        $this->assertSame('key', $expr->getKey());
    }

    public function testWithTableNameReturnsCloneAndDoesNotMutateOriginal(): void
    {
        $original = new ForeignKeyExpression('table', 'field');
        $clone = $original->withTableName('other_table');

        $this->assertNotSame($original, $clone);

        $this->assertSame('table', $original->getTableName());
        $this->assertSame('other_table', $clone->getTableName());
        $this->assertSame($original->getColumnExpression(), $clone->getColumnExpression());
        $this->assertSame($original->getColumnName(), $clone->getColumnName());
        $this->assertSame($original->getKey(), $clone->getKey());
    }
}
