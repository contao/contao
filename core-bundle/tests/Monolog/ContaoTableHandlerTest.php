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
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

class ContaoTableHandlerTest extends TestCase
{
    public function testHandlesContaoRecords(): void
    {
        $record = new LogRecord(new \DateTimeImmutable(), 'test', Level::Debug, 'foobar', ['contao' => new ContaoContext('foobar')], []);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('insert')
            ->willReturn(1)
        ;

        $handler = new ContaoTableHandler(static fn () => $connection);

        var_dump($handler);
        $this->assertFalse($handler->handle($record));
    }

    public function testDoesNotHandleARecordIfTheLogLevelDoesNotMatch(): void
    {
        $record = new LogRecord(new \DateTimeImmutable(), 'test', Level::Debug, 'foobar', [], []);

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
        $record = new LogRecord(new \DateTimeImmutable(), 'test', Level::Debug, 'foobar', ['contao' => null], []);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('insert')
        ;

        $handler = new ContaoTableHandler(static fn () => $connection);

        $this->assertFalse($handler->handle($record));
    }
}
