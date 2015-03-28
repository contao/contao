<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\ExceptionListener;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests the ExceptionListenerTest class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ExceptionListenerTest extends TestCase
{
    /**
     * Returns the path to the fictional kernel directory.
     *
     * @return string The kernel directory path
     */
    public function getKernelDir()
    {
        return parent::getRootDir() . '/app';
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new ExceptionListener(true, $this->getKernelDir());

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\ExceptionListener', $listener);
    }

    /**
     * Tests that no error screen is generated when none shall be created.
     */
    public function testGenericExceptionWithoutErrorScreen()
    {
        $listener = new ExceptionListener(false, $this->getKernelDir());

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            new \Exception('test')
        );

        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * Tests that the generic error screen is generated for all exceptions unknown.
     */
    public function testGenericExceptionWithErrorScreen()
    {
        $listener = new ExceptionListener(true, $this->getKernelDir());

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            new \Exception('test')
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());

        $this->assertEquals(
            file_get_contents($this->getRootDir() . '/templates/be_error.html5'),
            $event->getResponse()->getContent()
        );
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));
        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests that the fallback message is shown when the error screen template is not available.
     */
    public function testGenericExceptionWithErrorScreenWithoutTemplate()
    {
        $listener = new ExceptionListener(true, $this->getKernelDir() . '/non-existant/path');

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            new \Exception('test')
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));
        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }


    /**
     * Test that any unknown exception extending HttpExceptionInterface is rendered as the standard be_error screen.
     */
    public function testUnknownHttpExceptionIsRenderedAsError()
    {
        $listener = new ExceptionListener(true, $this->getKernelDir());

        /** @var \Exception $exception */
        $exception = $this->getMockForAbstractClass(
            'Contao\CoreBundle\Test\EventListener\ExceptionListenerTestHelperHttpException',
            array('test')
        );

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());

        $this->assertEquals(
            file_get_contents($this->getRootDir() . '/templates/be_error.html5'),
            $event->getResponse()->getContent()
        );
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));
        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
        $this->assertEquals($event->getResponse()->getStatusCode(), 500);
    }

    /**
     * Data provider for testing all known Contao exceptions thrown by the framework and rendered via template.
     */
    public function knownContaoExceptions()
    {
        $reflection = new \ReflectionProperty(
            'Contao\CoreBundle\EventListener\ExceptionListener',
            'exceptionTemplates'
        );
        $reflection->setAccessible(true);

        $classMap = $reflection->getValue();

        return array_map(function($class) use ($classMap) { return [$class, $classMap[$class]]; }, array_keys($classMap));
    }

    /**
     * Tests that the correct error screen template is not rendered.
     *
     * @dataProvider knownContaoExceptions
     *
     * @param string $exceptionClass The exception to be handled.
     * @param string $templateName   The expected template to be rendered.
     */
    public function testKnownContaoExceptionRendersTemplate($exceptionClass, $templateName)
    {
        $listener  = new ExceptionListener(true, $this->getKernelDir());
        $exception = new $exceptionClass();
        $event     = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());
        $this->assertEquals(
            file_get_contents($this->getRootDir() . '/templates/' . $templateName . '.html5'),
            $event->getResponse()->getContent()
        );
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));
        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests that no template is rendered when there should not.
     *
     * @dataProvider knownContaoExceptions
     *
     * @param string $exceptionClass The exception to be handled.
     */
    public function testKnownContaoExceptionDoesNotRenderTemplate($exceptionClass)
    {
        $listener  = new ExceptionListener(false, $this->getKernelDir());
        $exception = new $exceptionClass();
        $event     = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * Tests that the fallback message is shown when the error screen template is not available.
     *
     * @dataProvider knownContaoExceptions
     *
     * @param string $exceptionClass The exception to be handled.
     * @param string $templateName   The expected template to be rendered.
     */
    public function testWrappedKnownContaoExceptionRendersTemplate($exceptionClass, $templateName)
    {
        $listener  = new ExceptionListener(true, $this->getKernelDir());
        $exception = new \RuntimeException(
            'wrap 1',
            1,
            new \LogicException(
                'It is logical to chain exceptions.',
                1,
                new $exceptionClass('I got chained.')
            )
        );
        $event     = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());
        $this->assertEquals(
            file_get_contents($this->getRootDir() . '/templates/' . $templateName . '.html5'),
            $event->getResponse()->getContent()
        );
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));
        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Test that response exceptions are handled correctly.
     */
    public function testResponseExceptionIsHandled()
    {
        $listener      = new ExceptionListener(true, $this->getKernelDir());
        $exception     = ResponseException::create('I got chained.');
        $wrapException = new \RuntimeException(
            'wrap 1',
            1,
            new \LogicException(
                'It is logical to chain exceptions.',
                1,
                $exception
            )
        );
        $event     = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $wrapException
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());
        $this->assertSame($event->getResponse(), $exception->getResponse());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));
        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    }
}
