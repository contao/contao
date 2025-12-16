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
    #[DataProvider('quoteIdentifierProvider')]
    public function testQuoteIdentifier(string $identifier, string $expected): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quoteIdentifier')
            ->willReturnCallback(static fn ($v) => implode('.', array_map(static fn ($vv) => "`$vv`", explode('.', $identifier))))
        ;

        if (method_exists(Connection::class, 'quoteSingleIdentifier')) {
            $connection
                ->method('quoteSingleIdentifier')
                ->willReturnCallback(static fn ($v) => "`$v`")
            ;
        }

        $util = new DatabaseUtil($connection);

        $this->assertSame($expected, $util->quoteIdentifier($identifier));
    }

    public static function quoteIdentifierProvider(): iterable
    {
        yield [
            'foo',
            '`foo`',
        ];

        yield [
            'foo.bar',
            '`foo`.`bar`',
        ];

        yield [
            'foo.`bar`',
            'foo.`bar`',
        ];
    }
}
