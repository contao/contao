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

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\EventListener\CommandSchedulerListener;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class CommandSchedulerListenerTest extends TestCase
{
    public function testRunsTheCommandSchedulerIfAutoModeIsDisabled(): void
    {
        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->never())
            ->method('hasMinutelyCliCron')
        ;

        $cron
            ->expects($this->once())
            ->method('run')
            ->with(Cron::SCOPE_WEB)
        ;

        $listener = new CommandSchedulerListener($cron, $this->mockConnection());
        $listener($this->getTerminateEvent('contao_frontend'));
    }

    public function testRunsTheCommandSchedulerIfAutoModeIsEnabledAndCronDoesNotExist(): void
    {
        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->once())
            ->method('hasMinutelyCliCron')
            ->willReturn(false)
        ;

        $cron
            ->expects($this->once())
            ->method('run')
            ->with(Cron::SCOPE_WEB)
        ;

        $listener = new CommandSchedulerListener($cron, $this->mockConnection(), '_fragment', true);
        $listener($this->getTerminateEvent('contao_frontend'));
    }

    public function testDoesNotRunTheCommandSchedulerIfAutoModeIsEnabledAndCronExists(): void
    {
        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->once())
            ->method('hasMinutelyCliCron')
            ->willReturn(true)
        ;

        $cron
            ->expects($this->never())
            ->method('run')
        ;

        $listener = new CommandSchedulerListener($cron, $this->mockConnection(), '_fragment', true);
        $listener($this->getTerminateEvent('contao_frontend'));
    }

    public function testDoesNotRunTheCommandSchedulerUponFragmentRequests(): void
    {
        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->never())
            ->method('run')
        ;

        $ref = new \ReflectionClass(Request::class);
        $request = $ref->newInstance();

        $pathInfo = $ref->getProperty('pathInfo');
        $pathInfo->setValue($request, '/foo/_fragment/bar');

        $event = new TerminateEvent($this->createMock(KernelInterface::class), $request, new Response());

        $listener = new CommandSchedulerListener($cron, $this->mockConnection());
        $listener($event);
    }

    public function testDoesNotRunTheCommandSchedulerIfThereIsADatabaseConnectionError(): void
    {
        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->never())
            ->method('run')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('isConnected')
            ->willThrowException($this->createMock(DriverException::class))
        ;

        $listener = new CommandSchedulerListener($cron, $connection);
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

    private function getTerminateEvent(string|null $route = null): TerminateEvent
    {
        $request = new Request();

        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        return new TerminateEvent($this->createMock(KernelInterface::class), $request, new Response());
    }
}
