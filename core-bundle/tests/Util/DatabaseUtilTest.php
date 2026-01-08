<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Util;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\DatabaseUtil;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;

class DatabaseUtilTest extends TestCase
{
    #[DataProvider('parseForeignKeyExpressionProvider')]
    public function testParseForeignKeyExpression(string $foreignKeyDefinition, bool $expectsQuotingCall, string $expectedTableName, string $expectedColumnExpression, string|null $expectedColumnName = null, string|null $expectedKey = null): void
    {
        $connection = $this->mockConnection($expectsQuotingCall);
        $util = new DatabaseUtil($connection);

        $expression = $util->parseForeignKeyExpression($foreignKeyDefinition);
        $this->assertSame($expectedTableName, $expression->getTableName());
        $this->assertSame($expectedColumnExpression, $expression->getColumnExpression());
        $this->assertSame($expectedColumnName, $expression->getColumnName());
        $this->assertSame($expectedKey, $expression->getKey());
    }

    public static function parseForeignKeyExpressionProvider(): iterable
    {
        yield [
            'table.field',
            true,
            'table',
            '`field`',
            'field',
        ];

        yield [
            'table.CONCAT(foobar, id)',
            false,
            'table',
            'CONCAT(foobar, id)',
        ];

        yield [
            'table.`bar`',
            false,
            'table',
            '`bar`',
        ];

        yield [
            'name:table.CONCAT(foobar, id)',
            false,
            'table',
            'CONCAT(foobar, id)',
            null,
            'name',
        ];
    }

    private function mockConnection(bool $expectsQuotingCall): Connection
    {
        $connection = $this->createMock(Connection::class);

        if (method_exists(Connection::class, 'quoteSingleIdentifier')) {
            $connection
                ->expects($expectsQuotingCall ? $this->once() : $this->never())
                ->method('quoteSingleIdentifier')
                ->willReturnCallback(static fn ($v) => "`$v`")
            ;
        } else {
            $connection
                ->expects($expectsQuotingCall ? $this->once() : $this->never())
                ->method('quoteIdentifier')
                ->willReturnCallback(static fn ($v) => "`$v`")
            ;
        }

        return $connection;
    }
}
