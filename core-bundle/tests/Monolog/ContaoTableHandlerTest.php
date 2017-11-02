<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Monolog;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\ContaoTableHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Monolog\Logger;

class ContaoTableHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = new ContaoTableHandler();

        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoTableHandler', $handler);
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
     *
     * @expectedDeprecation Using the addLogEntry hook has been deprecated %s.
     */
    public function testHandlesContaoRecords(): void
    {
        $record = [
            'level' => Logger::DEBUG,
            'extra' => ['contao' => new ContaoContext('foobar')],
            'context' => [],
            'datetime' => new \DateTime(),
            'message' => 'foobar',
        ];

        $statement = $this->createMock(Statement::class);

        $statement
            ->expects($this->once())
            ->method('execute')
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('prepare')
            ->willReturn($statement)
        ;

        $system = $this->mockConfiguredAdapter(['importStatic' => $this]);

        $container = $this->mockContainer();
        $container->set('contao.framework', $this->mockContaoFramework([System::class => $system]));
        $container->set('doctrine.dbal.default_connection', $connection);

        $GLOBALS['TL_HOOKS']['addLogEntry'][] = [\get_class($this), 'addLogEntry'];

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

        $this->assertFalse($handler->handle(['level' => Logger::DEBUG]));
    }

    public function testDoesNotHandleARecordWithoutContaoContext(): void
    {
        $record = [
            'level' => Logger::DEBUG,
            'extra' => ['contao' => null],
            'context' => [],
        ];

        $handler = new ContaoTableHandler();

        $this->assertFalse($handler->handle($record));
    }

    public function testDoesNotHandleTheRecordIfThereIsNoContainer(): void
    {
        $record = [
            'level' => Logger::DEBUG,
            'extra' => ['contao' => new ContaoContext('foobar')],
            'context' => [],
            'datetime' => new \DateTime(),
            'message' => 'foobar',
        ];

        $handler = new ContaoTableHandler();

        $this->assertFalse($handler->handle($record));
    }
}
