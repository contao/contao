<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\Config;
use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\EventListener\CommandSchedulerListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandSchedulerListenerTest extends TestCase
{
    public function testRunsTheCommandScheduler(): void
    {
        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->once())
            ->method('run')
            ->with(Cron::SCOPE_WEB)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->method('get')
            ->with('contao.cron')
            ->willReturn($cron)
        ;

        $listener = new CommandSchedulerListener($locator, $this->mockContaoFramework(), $this->mockConnection());
        $listener($this->getTerminateEvent('contao_frontend'));
    }

    public function testDoesNotRunTheCommandSchedulerIfTheContaoFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $listener = new CommandSchedulerListener($this->createMock(ContainerInterface::class), $framework, $this->mockConnection());
        $listener($this->getTerminateEvent('contao_backend'));
    }

    public function testDoesNotRunTheCommandSchedulerInTheInstallTool(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $ref = new \ReflectionClass(Request::class);

        /** @var Request $request */
        $request = $ref->newInstance();

        $pathInfo = $ref->getProperty('pathInfo');
        $pathInfo->setAccessible(true);
        $pathInfo->setValue($request, '/contao/install');

        $event = new TerminateEvent($this->createMock(KernelInterface::class), $request, new Response());

        $listener = new CommandSchedulerListener($this->createMock(ContainerInterface::class), $framework, $this->mockConnection());
        $listener($event);
    }

    public function testDoesNotRunTheCommandSchedulerUponFragmentRequests(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $ref = new \ReflectionClass(Request::class);

        /** @var Request $request */
        $request = $ref->newInstance();

        $pathInfo = $ref->getProperty('pathInfo');
        $pathInfo->setAccessible(true);
        $pathInfo->setValue($request, '/foo/_fragment/bar');

        $event = new TerminateEvent($this->createMock(KernelInterface::class), $request, new Response());

        $listener = new CommandSchedulerListener($this->createMock(ContainerInterface::class), $framework, $this->mockConnection());
        $listener($event);
    }

    public function testDoesNotRunTheCommandSchedulerIfTheInstallationIsIncomplete(): void
    {
        $adapter = $this->mockAdapter(['isComplete', 'get']);
        $adapter
            ->method('isComplete')
            ->willReturn(false)
        ;

        $adapter
            ->expects($this->never())
            ->method('get')
        ;

        $framework = $this->mockContaoFramework([Config::class => $adapter]);
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $listener = new CommandSchedulerListener($this->createMock(ContainerInterface::class), $framework, $this->mockConnection());
        $listener($this->getTerminateEvent('contao_backend'));
    }

    public function testDoesNotRunTheCommandSchedulerIfCronjobsAreDisabled(): void
    {
        $adapter = $this->mockAdapter(['isComplete', 'get']);
        $adapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $adapter
            ->method('get')
            ->with('disableCron')
            ->willReturn(true)
        ;

        $framework = $this->mockContaoFramework([Config::class => $adapter]);
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $listener = new CommandSchedulerListener($this->createMock(ContainerInterface::class), $framework, $this->mockConnection());
        $listener($this->getTerminateEvent('contao_frontend'));
    }

    public function testDoesNotRunTheCommandSchedulerIfThereIsADatabaseConnectionError(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('getAdapter')
        ;

        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->never())
            ->method('run')
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->method('get')
            ->with('contao.cron')
            ->willReturn($cron)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('isConnected')
            ->willThrowException($this->createMock(DriverException::class))
        ;

        $listener = new CommandSchedulerListener($locator, $framework, $connection);
        $listener($this->getTerminateEvent('contao_backend'));
    }

    /**
     * @return Connection&MockObject
     */
    private function mockConnection(): Connection
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
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
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        return $connection;
    }

    private function getTerminateEvent(string $route = null): TerminateEvent
    {
        $request = new Request();

        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        return new TerminateEvent($this->createMock(KernelInterface::class), $request, new Response());
    }
}
