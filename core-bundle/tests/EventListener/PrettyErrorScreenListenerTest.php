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

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\PrettyErrorScreenListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class PrettyErrorScreenListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = $this->mockListener(FrontendUser::class);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\PrettyErrorScreenListener', $listener);
    }

    public function testRendersBackEndExceptions(): void
    {
        $exception = new InternalServerErrorHttpException('', new InternalServerErrorException());
        $responseEvent = $this->mockResponseEvent($exception);

        $listener = $this->mockListener(BackendUser::class, true);
        $listener->onKernelException($responseEvent);

        $this->assertTrue($responseEvent->hasResponse());

        $response = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(500, $response->getStatusCode());
    }

    /**
     * @param int        $type
     * @param \Exception $exception
     *
     * @dataProvider getErrorTypes
     */
    public function testRendersTheContaoPageHandler($type, \Exception $exception): void
    {
        $GLOBALS['TL_PTY']['error_'.$type] = 'Contao\PageError'.$type;

        $responseEvent = $this->mockResponseEvent($exception);

        $listener = $this->mockListener(FrontendUser::class);
        $listener->onKernelException($responseEvent);

        $this->assertTrue($responseEvent->hasResponse());

        $response = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame($type, $response->getStatusCode());

        unset($GLOBALS['TL_PTY']);
    }

    /**
     * @return array
     */
    public function getErrorTypes(): array
    {
        return [
            [403, new AccessDeniedHttpException('', new AccessDeniedException())],
            [404, new NotFoundHttpException('', new PageNotFoundException())],
        ];
    }

    public function testHandlesResponseExceptionsWhenRenderingAPageHandler(): void
    {
        $GLOBALS['TL_PTY']['error_403'] = 'Contao\PageErrorResponseException';

        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $responseEvent = $this->mockResponseEvent($exception);

        $listener = $this->mockListener(FrontendUser::class);
        $listener->onKernelException($responseEvent);

        $this->assertTrue($responseEvent->hasResponse());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $responseEvent->getResponse());

        unset($GLOBALS['TL_PTY']);
    }

    public function testHandlesExceptionsWhenRenderingAPageHandler(): void
    {
        $GLOBALS['TL_PTY']['error_403'] = 'Contao\PageErrorException';

        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $responseEvent = $this->mockResponseEvent($exception);

        $listener = $this->mockListener(FrontendUser::class);
        $listener->onKernelException($responseEvent);

        $this->assertFalse($responseEvent->hasResponse());

        unset($GLOBALS['TL_PTY']);
    }

    public function testRendersServiceUnavailableHttpExceptions(): void
    {
        $exception = new ServiceUnavailableHttpException('', new ServiceUnavailableException());
        $responseEvent = $this->mockResponseEvent($exception);

        $listener = $this->mockListener(FrontendUser::class);
        $listener->onKernelException($responseEvent);

        $this->assertTrue($responseEvent->hasResponse());

        $response = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(503, $response->getStatusCode());
    }

    public function testRendersUnknownHttpExceptions(): void
    {
        $responseEvent = $this->mockResponseEvent(new ConflictHttpException());

        $listener = $this->mockListener(FrontendUser::class, true);
        $listener->onKernelException($responseEvent);

        $this->assertTrue($responseEvent->hasResponse());

        $response = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(409, $response->getStatusCode());
    }

    public function testRendersTheErrorScreen(): void
    {
        $exception = new InternalServerErrorHttpException('', new ForwardPageNotFoundException());
        $responseEvent = $this->mockResponseEvent($exception);
        $twig = $this->createMock('Twig_Environment');
        $count = 0;

        $twig
            ->method('render')
            ->willReturnCallback(function () use (&$count): void {
                if (0 === $count++) {
                    throw new \Twig_Error('foo');
                }
            })
        ;

        $listener = $this->mockListener(FrontendUser::class, true, $twig);
        $listener->onKernelException($responseEvent);

        $this->assertTrue($responseEvent->hasResponse());

        $response = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testDoesNothingIfTheFormatIsNotHtml(): void
    {
        $request = new Request();
        $request->attributes->set('_format', 'json');

        $exception = new InternalServerErrorHttpException('', new InsecureInstallationException());
        $responseEvent = $this->mockResponseEvent($exception, $request);

        $listener = $this->mockListener(FrontendUser::class);
        $listener->onKernelException($responseEvent);

        $this->assertFalse($responseEvent->hasResponse());
    }

    public function testDoesNothingIfThePageHandlerDoesNotExist(): void
    {
        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $event = $this->mockResponseEvent($exception);

        $listener = $this->mockListener(FrontendUser::class);
        $listener->onKernelException($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Mocks a pretty error screen listener.
     *
     * @param string                 $userClass
     * @param bool                   $expectLogging
     * @param \Twig_Environment|null $twig
     *
     * @return PrettyErrorScreenListener
     */
    private function mockListener(string $userClass, bool $expectLogging = false, \Twig_Environment $twig = null): PrettyErrorScreenListener
    {
        if (null === $twig) {
            $twig = $this->createMock('Twig_Environment');
        }

        $framework = $this->mockContaoFramework();
        $tokenStorage = $this->mockTokenStorage($userClass);
        $scopeMatcher = $this->mockScopeMatcher();
        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($expectLogging ? $this->once() : $this->never())
            ->method('critical')
        ;

        return new PrettyErrorScreenListener(true, $twig, $framework, $tokenStorage, $scopeMatcher, $logger);
    }

    /**
     * Mocks a response event.
     *
     * @param \Exception   $exception
     * @param Request|null $request
     *
     * @return GetResponseForExceptionEvent
     */
    private function mockResponseEvent(\Exception $exception, Request $request = null): GetResponseForExceptionEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = new Request();
            $request->attributes->set('_scope', 'backend');
        }

        return new GetResponseForExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
    }
}
