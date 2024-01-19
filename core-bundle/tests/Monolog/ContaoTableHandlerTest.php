<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Monolog;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\ContaoTableHandler;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Monolog\Logger;

class ContaoTableHandlerTest extends TestCase
{
    public function testHandlesContaoRecords(): void
    {
        $record = [
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => 'test',
            'extra' => ['contao' => new ContaoContext('foobar')],
            'context' => [],
            'datetime' => new \DateTimeImmutable(),
            'message' => 'foobar',
        ];

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('insert')
            ->willReturn(1)
        ;

        $handler = new ContaoTableHandler(static fn () => $connection);

        $this->assertFalse($handler->handle($record));
    }

    public function testDoesNotHandleARecordIfTheLogLevelDoesNotMatch(): void
    {
        $record = [
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => 'test',
            'extra' => [],
            'context' => [],
            'datetime' => new \DateTimeImmutable(),
            'message' => 'foobar',
        ];

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('insert')
        ;

        $handler = new ContaoTableHandler(static fn () => $connection);
        $handler->setLevel(Logger::INFO);

        $this->assertFalse($handler->handle($record));
    }

    public function testDoesNotHandleARecordWithoutContaoContext(): void
    {
        $record = [
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => 'test',
            'extra' => ['contao' => null],
            'context' => [],
            'datetime' => new \DateTimeImmutable(),
            'message' => 'foobar',
        ];

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('insert')
        ;

        $handler = new ContaoTableHandler(static fn () => $connection);

        $this->assertFalse($handler->handle($record));
    }
}
