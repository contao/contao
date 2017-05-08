<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\CommandSchedulerListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendCron;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\MySqlSchemaManager;

/**
 * Tests the CommandSchedulerListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class CommandSchedulerListenerTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);

        $this->framework
            ->method('getAdapter')
            ->willReturn($this->mockConfigAdapter())
        ;
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new CommandSchedulerListener($this->framework, $this->mockConnection());

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\CommandSchedulerListener', $listener);
    }

    /**
     * Tests that the listener does nothing if the Contao framework is not booted.
     */
    public function testWithoutContaoFramework()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $this->framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $listener = new CommandSchedulerListener($this->framework, $this->mockConnection());
        $listener->onKernelTerminate();
    }

    /**
     * Tests that the listener does use the response if the Contao framework is booted.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithContaoFramework()
    {
        $this->framework
            ->expects($this->once())
            ->method('getAdapter')
        ;

        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $controller = $this->createMock(FrontendCron::class);

        $controller
            ->expects($this->once())
            ->method('run')
        ;

        $this->framework
            ->method('createInstance')
            ->willReturn($controller)
        ;

        $listener = new CommandSchedulerListener($this->framework, $this->mockConnection());
        $listener->onKernelTerminate();
    }

    /**
     * Tests that the listener does nothing if the installation is incomplete.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIncompleteInstallation()
    {
        $adapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'isComplete'])
            ->getMock()
        ;

        $adapter
            ->expects($this->never())
            ->method('get')
        ;

        $adapter
            ->method('isComplete')
            ->willReturn(false)
        ;

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);

        $this->framework
            ->method('getAdapter')
            ->willReturn($adapter)
        ;

        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $this->framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $listener = new CommandSchedulerListener($this->framework, $this->mockConnection());
        $listener->onKernelTerminate();
    }

    /**
     * Tests that the listener does nothing if the command scheduler has been disabled.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDisableCron()
    {
        $adapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'isComplete'])
            ->getMock()
        ;

        $adapter
            ->method('get')
            ->willReturn(true)
        ;

        $adapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);

        $this->framework
            ->method('getAdapter')
            ->willReturn($adapter)
        ;

        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $this->framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $listener = new CommandSchedulerListener($this->framework, $this->mockConnection());
        $listener->onKernelTerminate();
    }

    /**
     * Mocks a database connection object.
     *
     * @return Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConnection()
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);

        $schemaManager
            ->method('tablesExist')
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('isConnected')
            ->willReturn(true)
        ;

        $connection
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        return $connection;
    }
}
