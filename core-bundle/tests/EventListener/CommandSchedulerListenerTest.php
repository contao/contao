<?php

declare(strict_types=1);

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
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandSchedulerListenerTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);

        $this->framework
            ->method('getAdapter')
            ->willReturn($this->mockConfigAdapter())
        ;
    }

    public function testCanBeInstantiated(): void
    {
        $listener = new CommandSchedulerListener($this->framework, $this->mockConnection());

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\CommandSchedulerListener', $listener);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRunsTheCommandScheduler(): void
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

    public function testDoesNotRunTheCommandSchedulerIfTheContaoFrameworkIsNotInitialized(): void
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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotRunTheCommandSchedulerInTheInstallTool(): void
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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotRunTheCommandSchedulerUponFragmentRequests(): void
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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotRunTheCommandSchedulerIfTheInstallationIsIncomplete(): void
    {
        $adapter = $this->createMock(Adapter::class);

        $adapter
            ->method('__call')
            ->willReturnCallback(
                function (string $method) {
                    switch ($method) {
                        case 'isComplete':
                            return false;

                        case 'get':
                            $this->fail('The get() method should never be called');
                    }

                    return null;
                }
            )
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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotRunTheCommandSchedulerIfCronjobsAreDisabled(): void
    {
        $adapter = $this->createMock(Adapter::class);

        $adapter
            ->method('__call')
            ->willReturnCallback(
                function (string $method) {
                    switch ($method) {
                        case 'isComplete':
                        case 'get':
                            return true;
                    }

                    return null;
                }
            )
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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotRunTheCommandSchedulerIfThereIsADatabaseConnectionError(): void
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
            ->willThrowException(new DriverException('Could not connect', new MysqliException('Invalid password')))
        ;

        $listener = new CommandSchedulerListener($this->framework, $connection);
        $listener->onKernelTerminate($this->mockPostResponseEvent('contao_backend'));
    }

    /**
     * Mocks a database connection.
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
    private function mockPostResponseEvent($route = null): PostResponseEvent
    {
        $request = new Request();

        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        return new PostResponseEvent($this->createMock(KernelInterface::class), $request, new Response());
    }
}
