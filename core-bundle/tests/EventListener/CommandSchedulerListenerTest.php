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
use Doctrine\DBAL\Driver\Mysqli\MysqliException;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

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
        $listener->onKernelTerminate($this->mockPostResponseEvent('contao_backend'));
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
        $listener->onKernelTerminate($this->mockPostResponseEvent('contao_frontend'));
    }

    /**
     * Tests that the listener does nothing in the install tool.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInstallTool()
    {
        $this->framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $ref = new \ReflectionClass(Request::class);

        /** @var Request $request */
        $request = $ref->newInstance();

        $pathInfo = $ref->getProperty('pathInfo');
        $pathInfo->setAccessible(true);
        $pathInfo->setValue($request, '/contao/install');

        $event = new PostResponseEvent($this->createMock(KernelInterface::class), $request, new Response());

        $listener = new CommandSchedulerListener($this->framework, $this->mockConnection());
        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing upon a fragment URL.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFragmentUrl()
    {
        $this->framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $ref = new \ReflectionClass(Request::class);

        /** @var Request $request */
        $request = $ref->newInstance();

        $pathInfo = $ref->getProperty('pathInfo');
        $pathInfo->setAccessible(true);
        $pathInfo->setValue($request, '/foo/_fragment/bar');

        $event = new PostResponseEvent($this->createMock(KernelInterface::class), $request, new Response());

        $listener = new CommandSchedulerListener($this->framework, $this->mockConnection());
        $listener->onKernelTerminate($event);
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
        $listener->onKernelTerminate($this->mockPostResponseEvent('contao_backend'));
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
        $listener->onKernelTerminate($this->mockPostResponseEvent('contao_frontend'));
    }

    /**
     * Tests that the listener does nothing if the database connection fails.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDatabaseConnectionError()
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
            ->expects($this->never())
            ->method('run')
        ;

        $this->framework
            ->method('createInstance')
            ->willReturn($controller)
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('isConnected')
            ->willThrowException(new ConnectionException('Could not connect', new MysqliException('Invalid password')))
        ;

        $listener = new CommandSchedulerListener($this->framework, $connection);
        $listener->onKernelTerminate($this->mockPostResponseEvent('contao_backend'));
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

    /**
     * Mocks a post response event.
     *
     * @param string|null $route
     *
     * @return PostResponseEvent
     */
    private function mockPostResponseEvent($route = null)
    {
        $request = new Request();

        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        return new PostResponseEvent($this->createMock(KernelInterface::class), $request, new Response());
    }
}
