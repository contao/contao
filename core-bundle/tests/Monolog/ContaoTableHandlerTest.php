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
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('insert')
            ->willReturn(1)
        ;

        $handler = new ContaoTableHandler(static fn () => $connection);
        $record = $this->getRecord(['contao' => new ContaoContext('foobar')]);

        $this->assertFalse($handler->handle($record));
    }

    public function testDoesNotHandleARecordIfTheLogLevelDoesNotMatch(): void
    {
        $record = $this->getRecord([]);

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
        $record = $this->getRecord(['contao' => null]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('insert')
        ;

        $handler = new ContaoTableHandler(static fn () => $connection);

        $this->assertFalse($handler->handle($record));
    }

    /**
     * The Contao context was moved to the "extra" section by the processor, so pass
     * it as sixth argument to the LogRecord class.
     */
    private function getRecord(array $context): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'test', Level::Debug, 'foobar', [], $context);
    }
}
