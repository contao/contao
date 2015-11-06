<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\PrettyErrorScreenListener;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Test\TestCase;
use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

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

        /** @var \Twig_Environment $twig */
        $twig = $this
            ->getMockBuilder('Twig_Environment')
            ->setConstructorArgs([$this->getMock('Twig_LoaderInterface')])
            ->getMock()
        ;

        /** @var LoggerInterface $logger */
        $logger = $this->getMock('Psr\Log\LoggerInterface');

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
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\EventListener\PrettyErrorScreenListener', $this->listener);
    }

    /**
     * Tests rendering an internal server error HTTP exception.
     */
    public function testInternalServerErrorHttpException()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new InternalServerErrorHttpException('', new InsecureInstallationException())
        );

        $this->listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * Tests rendering a service unavailable HTTP exception.
     */
    public function testServiceUnavailableHttpException()
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
        $this->assertEquals(503, $response->getStatusCode());
    }

    /**
     * Tests rendering an unknown HTTP exception.
     */
    public function testUnknownHttpException()
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
        $this->assertEquals(409, $response->getStatusCode());
    }

    /**
     * Tests rendering the Contao page handler.
     */
    public function testContaoPageHandler()
    {
        $GLOBALS['TL_PTY']['error_404'] = 'Contao\PageError404';

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new NotFoundHttpException('', new PageNotFoundException())
        );

        $this->listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(404, $response->getStatusCode());

        unset($GLOBALS['TL_PTY']);
    }

    /**
     * Tests rendering the error screen.
     */
    public function testErrorScreen()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new InternalServerErrorHttpException('', new ForwardPageNotFoundException())
        );

        $count = 0;

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $twig */
        $twig = $this
            ->getMockBuilder('Twig_Environment')
            ->setMethods(['render'])
            ->setConstructorArgs([$this->getMock('Twig_LoaderInterface')])
            ->getMock()
        ;

        $twig
            ->expects($this->any())
            ->method('render')
            ->willReturnCallback(function () use (&$count) {
                if (0 === $count++) {
                    throw new \Twig_Error('foo');
                }
            })
        ;

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $logger->expects($this->once())->method('critical');

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
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * Mocks a token storage object.
     *
     * @return TokenStorage|\PHPUnit_Framework_MockObject_MockObject The token storage object
     */
    private function mockTokenStorage()
    {
        /** @var AbstractToken|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMockForAbstractClass('Symfony\Component\Security\Core\Authentication\Token\AbstractToken');

        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($this->getMock('Contao\BackendUser'))
        ;

        /** @var TokenStorage|\PHPUnit_Framework_MockObject_MockObject $tokenStorage */
        $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage');

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
    }
}
