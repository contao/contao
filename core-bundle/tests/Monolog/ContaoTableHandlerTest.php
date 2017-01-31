<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Monolog;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\ContaoTableHandler;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Monolog\Logger;

/**
 * Tests the ContaoTableHandler class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoTableHandlerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoTableHandler', new ContaoTableHandler());
    }

    /**
     * Tests setting and retrieving the DBAL service name.
     */
    public function testSetAndGetDbalServiceName()
    {
        $handler = new ContaoTableHandler();

        $this->assertEquals('doctrine.dbal.default_connection', $handler->getDbalServiceName());

        $handler->setDbalServiceName('foobar');

        $this->assertEquals('foobar', $handler->getDbalServiceName());
    }

    /**
     * Tests the handle() method.
     *
     * @expectedDeprecation Using the addLogEntry hook has been deprecated %s.
     * @group legacy
     */
    public function testHandle()
    {
        $record = [
            'level' => Logger::DEBUG,
            'extra' => ['contao' => new ContaoContext('foobar')],
            'context' => [],
            'datetime' => new \DateTime(),
            'message' => 'foobar',
        ];

        /** @var Statement|\PHPUnit_Framework_MockObject_MockObject $connection */
        $statement = $this->getMock('Doctrine\DBAL\Statement', ['execute'], [], '', false);

        $statement
            ->expects($this->once())
            ->method('execute')
        ;

        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['prepare'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('prepare')
            ->willReturn($statement)
        ;

        $container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['has', 'get'])
            ->getMock()
        ;

        $container
            ->expects($this->any())
            ->method('has')
            ->willReturn(true)
        ;

        $container
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key) use ($connection) {
                switch ($key) {
                    case 'contao.framework':
                        $system = $this
                            ->getMockBuilder('Contao\System')
                            ->disableOriginalConstructor()
                            ->getMockForAbstractClass()
                        ;

                        $framework = $this
                            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
                            ->disableOriginalConstructor()
                            ->getMock()
                        ;

                        $framework
                            ->expects($this->any())
                            ->method('isInitialized')
                            ->willReturn(true)
                        ;

                        $framework
                            ->expects($this->any())
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

    /**
     * Dummy method to test the addLogEntry hook.
     */
    public function addLogEntry()
    {
        // ignore
    }

    /**
     * Tests that the handler does nothing if the log level does not match.
     */
    public function testNotHandlingLogLevel()
    {
        $handler = new ContaoTableHandler();
        $handler->setLevel(Logger::INFO);

        $this->assertFalse($handler->handle(['level' => Logger::DEBUG]));
    }

    /**
     * Tests that the handle() method returns false if there is no Contao context.
     */
    public function testFalseWithoutContaoContext()
    {
        $record = [
            'level' => Logger::DEBUG,
            'extra' => ['contao' => null],
            'context' => [],
        ];

        $handler = new ContaoTableHandler();

        $this->assertFalse($handler->handle($record));
    }
}
