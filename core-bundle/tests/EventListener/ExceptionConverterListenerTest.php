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

use Contao\CoreBundle\EventListener\ExceptionConverterListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Fixtures\Exception\DerivedPageNotFoundException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ExceptionConverterListenerTest extends TestCase
{
    public function testConvertsAccessDeniedExceptions(): void
    {
        $event = $this->mockResponseEvent(new AccessDeniedException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(AccessDeniedHttpException::class, $exception);
        $this->assertInstanceOf(AccessDeniedException::class, $exception->getPrevious());
    }

    public function testConvertsForwardPageNotFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new ForwardPageNotFoundException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(ForwardPageNotFoundException::class, $exception->getPrevious());
    }

    public function testConvertsIncompleteInstallationExceptions(): void
    {
        $event = $this->mockResponseEvent(new IncompleteInstallationException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(IncompleteInstallationException::class, $exception->getPrevious());
    }

    public function testConvertsInsecureInstallationExceptions(): void
    {
        $event = $this->mockResponseEvent(new InsecureInstallationException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(InsecureInstallationException::class, $exception->getPrevious());
    }

    public function testConvertsInsufficientAuthenticationExceptions(): void
    {
        $event = $this->mockResponseEvent(new InsufficientAuthenticationException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(UnauthorizedHttpException::class, $exception);
        $this->assertInstanceOf(InsufficientAuthenticationException::class, $exception->getPrevious());
    }

    public function testConvertsInvalidRequestTokenExceptions(): void
    {
        $event = $this->mockResponseEvent(new InvalidRequestTokenException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(BadRequestHttpException::class, $exception);
        $this->assertInstanceOf(InvalidRequestTokenException::class, $exception->getPrevious());
    }

    public function testConvertsNoActivePageFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new NoActivePageFoundException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(NoActivePageFoundException::class, $exception->getPrevious());
    }

    public function testConvertsNoLayoutSpecifiedExceptions(): void
    {
        $event = $this->mockResponseEvent(new NoLayoutSpecifiedException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(NoLayoutSpecifiedException::class, $exception->getPrevious());
    }

    public function testConvertsNoRootPageFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new NoRootPageFoundException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(NoRootPageFoundException::class, $exception->getPrevious());
    }

    public function testConvertsPageNotFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new PageNotFoundException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(NotFoundHttpException::class, $exception);
        $this->assertInstanceOf(PageNotFoundException::class, $exception->getPrevious());
    }

    public function testConvertsResourceNotFoundExceptionWithoutRootPages(): void
    {
        $event = $this->mockResponseEvent(new NotFoundHttpException(null, new ResourceNotFoundException()));

        $listener = new ExceptionConverterListener($this->mockConnection(0));
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(NoRootPageFoundException::class, $exception->getPrevious());
    }

    public function testConvertsServiceUnavailableExceptions(): void
    {
        $event = $this->mockResponseEvent(new ServiceUnavailableException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(ServiceUnavailableHttpException::class, $exception);
        $this->assertInstanceOf(ServiceUnavailableException::class, $exception->getPrevious());
    }

    public function testConvertsUnknownExceptions(): void
    {
        $event = $this->mockResponseEvent(new \RuntimeException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('RuntimeException', $exception);
    }

    public function testConvertsDerivedPageNotFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new DerivedPageNotFoundException());

        $listener = new ExceptionConverterListener($this->mockConnection());
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf(NotFoundHttpException::class, $exception);
        $this->assertInstanceOf(PageNotFoundException::class, $exception->getPrevious());
    }

    private function mockResponseEvent(\Exception $exception): GetResponseForExceptionEvent
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = new Request();

        return new GetResponseForExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
    }

    /**
     * @return Connection|MockObject
     */
    private function mockConnection(int $rowCount = 1): Connection
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->method('fetchColumn')
            ->willReturn($rowCount)
        ;

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->method($this->logicalNot($this->equalTo('execute')))
            ->willReturnSelf()
        ;

        $queryBuilder
            ->method('execute')
            ->willReturn($statement)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        return $connection;
    }
}
