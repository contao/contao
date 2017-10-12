<?php

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
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Tests the PrettyErrorScreenListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PrettyErrorScreenListenerTest extends TestCase
{
    /**
     * @var PrettyErrorScreenListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        $GLOBALS['TL_LANG']['XPT'] = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $twig = $this->createMock('Twig_Environment');
        $logger = $this->createMock(LoggerInterface::class);

        $this->listener = new PrettyErrorScreenListener(
            true,
            $twig,
            $this->mockContaoFramework(),
            $this->mockTokenStorage(),
            $logger
        );
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\CoreBundle\EventListener\PrettyErrorScreenListener', $this->listener);
    }

    /**
     * Tests rendering a back end exception.
     */
    public function testRendersBackEndExceptions()
    {
        $twig = $this->createMock('Twig_Environment');
        $logger = $this->createMock(LoggerInterface::class);

        $this->listener = new PrettyErrorScreenListener(
            true,
            $twig,
            $this->mockContaoFramework(),
            $this->mockTokenStorage(BackendUser::class),
            $logger
        );

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new InternalServerErrorHttpException('', new InternalServerErrorException())
        );

        $this->listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(500, $response->getStatusCode());
    }

    /**
     * Tests rendering the Contao page handler.
     *
     * @param int        $type
     * @param \Exception $exception
     *
     * @dataProvider getErrorTypes
     */
    public function testRendersTheContaoPageHandler($type, \Exception $exception)
    {
        $GLOBALS['TL_PTY']['error_'.$type] = 'Contao\PageError'.$type;

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $this->listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame($type, $response->getStatusCode());

        unset($GLOBALS['TL_PTY']);
    }

    /**
     * Provides the data for the testContaoPageHandler() method.
     *
     * @return array
     */
    public function getErrorTypes()
    {
        return [
            [403, new AccessDeniedHttpException('', new AccessDeniedException())],
            [404, new NotFoundHttpException('', new PageNotFoundException())],
        ];
    }

    /**
     * Tests rendering a service unavailable HTTP exception.
     */
    public function testRendersServiceUnavailableHttpExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new ServiceUnavailableHttpException('', new ServiceUnavailableException())
        );

        $this->listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(503, $response->getStatusCode());
    }

    /**
     * Tests rendering an unknown HTTP exception.
     */
    public function testRendersUnknownHttpExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new ConflictHttpException()
        );

        $this->listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(409, $response->getStatusCode());
    }

    /**
     * Tests rendering the error screen.
     */
    public function testRendersTheErrorScreen()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new InternalServerErrorHttpException('', new ForwardPageNotFoundException())
        );

        $count = 0;

        $twig = $this->createMock('Twig_Environment');

        $twig
            ->method('render')
            ->willReturnCallback(function () use (&$count) {
                if (0 === $count++) {
                    throw new \Twig_Error('foo');
                }
            })
        ;

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('critical')
        ;

        $listener = new PrettyErrorScreenListener(
            true,
            $twig,
            $this->mockContaoFramework(),
            $this->mockTokenStorage(),
            $logger
        );

        $listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(500, $response->getStatusCode());
    }

    /**
     * Tests that the listener is bypassed if the request format is not "html".
     */
    public function testDoesNothingIfTheFormatIsNotHtml()
    {
        $request = new Request();
        $request->attributes->set('_format', 'json');

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new InternalServerErrorHttpException('', new InsecureInstallationException())
        );

        /** @var PrettyErrorScreenListener|\PHPUnit_Framework_MockObject_MockObject $listener */
        $listener = $this
            ->getMockBuilder(PrettyErrorScreenListener::class)
            ->disableOriginalConstructor()
            ->setMethods(['handleException'])
            ->getMock()
        ;

        $listener
            ->expects($this->never())
            ->method('handleException')
        ;

        $listener->onKernelException($event);
    }

    /**
     * Tests rendering a non existing page handler.
     */
    public function testDoesNothingIfThePageHandlerDoesNotExist()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new AccessDeniedHttpException('', new AccessDeniedException())
        );

        $this->listener->onKernelException($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Mocks a token storage object.
     *
     * @param string $userClass
     *
     * @return TokenStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTokenStorage($userClass = FrontendUser::class)
    {
        $token = $this->createMock(AbstractToken::class);

        $token
            ->method('getUser')
            ->willReturn($this->createMock($userClass))
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
    }
}
