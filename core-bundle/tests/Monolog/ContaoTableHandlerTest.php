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

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\ContaoTableHandler;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoTableHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoTableHandler', new ContaoTableHandler());
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

        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['has', 'get'])
            ->getMock()
        ;

        $container
            ->method('has')
            ->willReturn(true)
        ;

        $container
            ->method('get')
            ->willReturnCallback(function (string $key) use ($connection) {
                switch ($key) {
                    case 'contao.framework':
                        $system = $this->createMock(Adapter::class);

                        $system
                            ->method('__call')
                            ->willReturnCallback(
                                function (string $key): ?self {
                                    if ('importStatic' === $key) {
                                        return $this;
                                    }

                                    return null;
                                }
                            )
                        ;

                        $framework = $this->createMock(ContaoFrameworkInterface::class);

                        $framework
                            ->method('isInitialized')
                            ->willReturn(true)
                        ;

                        $framework
                            ->method('getAdapter')
                            ->willReturn($system)
                        ;

                        return $framework;

                    case 'doctrine.dbal.default_connection':
                        return $connection;
                }
            })
        ;

        $GLOBALS['TL_HOOKS']['addLogEntry'][] = [get_class($this), 'addLogEntry'];

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
