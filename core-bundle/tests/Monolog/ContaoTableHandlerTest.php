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
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Monolog\Logger;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class ContaoTableHandlerTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HOOKS']);

        parent::tearDown();
    }

    public function testSupportsReadingAndWritingTheDbalServiceName(): void
    {
        $handler = new ContaoTableHandler();

        $this->assertSame('doctrine.dbal.default_connection', $handler->getDbalServiceName());

        $handler->setDbalServiceName('foobar');

        $this->assertSame('foobar', $handler->getDbalServiceName());
    }

    /**
     * @group legacy
     */
    public function testHandlesContaoRecords(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.0: Using the "addLogEntry" hook has been deprecated %s.');

        $record = [
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => 'test',
            'extra' => ['contao' => new ContaoContext('foobar')],
            'context' => [],
            'datetime' => new \DateTimeImmutable(),
            'message' => 'foobar',
        ];

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('executeStatement')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('prepare')
            ->willReturn($statement)
        ;

        $system = $this->mockConfiguredAdapter(['importStatic' => $this]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework([System::class => $system]));
        $container->set('doctrine.dbal.default_connection', $connection);

        $GLOBALS['TL_HOOKS']['addLogEntry'][] = [static::class, 'addLogEntry'];

        $handler = new ContaoTableHandler();
        $handler->setContainer($container);

        $this->assertFalse($handler->handle($record));
    }

    public function addLogEntry(): void
    {
        // Dummy method to test the addLogEntry hook
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
