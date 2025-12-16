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
use Contao\CoreBundle\Util\ProcessUtil;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Promise\Is;
use GuzzleHttp\Promise\RejectionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\Process;

class DatabaseUtilTest extends TestCase
{
    #[DataProvider('quoteIdentifierProvider')]
    public function testQuoteIdentifier(string $identifier, string $expected): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method(method_exists(Connection::class, 'quoteSingleIdentifier') ? 'quoteSingleIdentifier' : 'quoteIdentifier')
            ->willReturnCallback(fn ($v) => "`$v`");

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
