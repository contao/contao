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
    public function testSupportsReadingAndWritingTheDbalServiceName(): void
    {
        $handler = new ContaoTableHandler();

        $this->assertSame('doctrine.dbal.default_connection', $handler->getDbalServiceName());

        $handler->setDbalServiceName('foobar');

        $this->assertSame('foobar', $handler->getDbalServiceName());
    }

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
            ->method('insert')
            ->willReturn(1)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('doctrine.dbal.default_connection', $connection);

        $handler = new ContaoTableHandler();
        $handler->setContainer($container);

        $this->assertFalse($handler->handle($record));
    }

    public function testDoesNotHandleARecordIfTheLogLevelDoesNotMatch(): void
    {
        $handler = new ContaoTableHandler();
        $handler->setLevel(Logger::INFO);

        $this->assertFalse($handler->handle([
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => 'test',
            'extra' => [],
            'context' => [],
            'datetime' => new \DateTimeImmutable(),
            'message' => 'foobar',
        ]));
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

        $handler = new ContaoTableHandler();

        $this->assertFalse($handler->handle($record));
    }

    public function testDoesNotHandleTheRecordIfThereIsNoContainer(): void
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

        $handler = new ContaoTableHandler();

        $this->assertFalse($handler->handle($record));
    }
}
