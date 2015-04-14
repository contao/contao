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
use Contao\CoreBundle\Exception\NoPagesFoundHttpException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Exception\RootNotFoundHttpException;
use Contao\CoreBundle\Test\TestCase;
use Contao\PageError404;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernel;

/**
 * Tests the ExceptionListenerTest class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ExceptionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());

        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\ExceptionListener', $listener);
    }

    /**
     * Tests that no error screen is generated if pretty_error_screens is disabled.
     */
    public function testGenericExceptionWithoutErrorScreen()
    {
        $this->microBootFramework();

        $listener = new ExceptionListener(false, $this->mockTwig(), $this->mockConfig());

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
     * Tests that the generic error screen is generated for an unknown exception.
     */
    public function testGenericExceptionWithErrorScreen()
    {
        $this->microBootFramework();

        $listener = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            new \Exception('test')
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());

        $this->assertEquals('error', $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests that any exception implementing the HttpExceptionInterface is rendered with the standard error screen.
     */
    public function testUnknownHttpExceptionIsRenderedAsError()
    {
        $this->microBootFramework();

        $listener = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());

        /** @var \Exception $exception */
        $exception = $this->getMockForAbstractClass(
            'Contao\\CoreBundle\\Test\\ExceptionListenerTestHelperHttpException',
            ['test']
        );

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());

        $this->assertEquals('error', $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );

        $this->assertEquals(500, $event->getResponse()->getStatusCode());
    }

    /**
     * Tests that a non existing template is rendered.
     */
    public function testNonExistentTemplateIsRenderedAsError()
    {
        $this->microBootFramework();

        $listener = new ExceptionListener(true, $this->mockTwig(['error']), $this->mockConfig());

        /** @var \Exception $exception */
        $exception = new NoPagesFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());

        $this->assertEquals('error', $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );

        $this->assertEquals(500, $event->getResponse()->getStatusCode());
    }

    /**
     * Tests that the correct error screen template is rendered.
     *
     * @param string $exceptionClass The exception to be handled.
     * @param string $templateName   The expected template to be rendered.
     *
     * @dataProvider knownContaoExceptions
     */
    public function testKnownContaoExceptionRendersTemplate($exceptionClass, $templateName)
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new $exceptionClass();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertEquals($templateName, $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests that no template is rendered if pretty_error_screens is disabled.
     *
     * @param string $exceptionClass The exception to be handled.
     *
     * @dataProvider knownContaoExceptions
     */
    public function testKnownContaoExceptionDoesNotRenderTemplate($exceptionClass)
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(false, $this->mockTwig(), $this->mockConfig());
        $exception = new $exceptionClass();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * Tests that the fallback message is shown when the error template is not available.
     *
     * @param string $exceptionClass The exception to be handled.
     * @param string $templateName   The expected template to be rendered.
     *
     * @dataProvider knownContaoExceptions
     */
    public function testWrappedKnownContaoExceptionRendersTemplate($exceptionClass, $templateName)
    {
        $this->microBootFramework();

        $listener = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());

        $exception = new \RuntimeException(
            'wrap 1',
            1,
            new \LogicException(
                'It is logical to chain exceptions.',
                1,
                new $exceptionClass('I got chained.')
            )
        );

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertEquals($templateName, $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests that response exceptions are handled correctly.
     */
    public function testResponseExceptionIsHandled()
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new ResponseException('I got chained.');

        $wrapException = new \RuntimeException(
            'wrap 1',
            1,
            new \LogicException(
                'It is logical to chain exceptions.',
                1,
                $exception
            )
        );

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $wrapException
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertSame($event->getResponse(), $exception->getResponse());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests rendering the Contao 404 page.
     */
    public function testTryToRenderContao404()
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new NotFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $response = new Response('the mocked response ' . time(), 404);

        PageError404::$getResponse = function () use ($response) { return $response; };

        $GLOBALS['TL_PTY']['error_404'] = 'PageError404';

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertSame($response, $event->getResponse());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests rendering the Contao 404 page without a root page.
     */
    public function testTryToRenderContao404WillNotRenderRootNotFoundHttpException()
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new RootNotFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        PageError404::$getResponse = function () { return new Response('FAIL!'); };

        $GLOBALS['TL_PTY']['error_404'] = 'PageError404';

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertEquals('no_root', $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests rendering the Contao 404 page without active pages.
     */
    public function testTryToRenderContao404WillNotRenderNoPagesFoundHttpException()
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new NoPagesFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        PageError404::$getResponse = function () { return new Response('FAIL!'); };

        $GLOBALS['TL_PTY']['error_404'] = 'PageError404';

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertEquals('no_active', $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests rendering the Contao 404 pages without a page handler.
     */
    public function testTryToRenderContao404WillNotRenderWithoutPageHandler()
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new NotFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        unset($GLOBALS['TL_PTY']['error_404']);

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertEquals('no_page', $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests rendering the Contao 404 page with an invalid page handler.
     */
    public function testTryToRenderContao404WillNotRenderWithInvalidPageHandler()
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new NotFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $GLOBALS['TL_PTY']['error_404'] = 'Non\\Existent\\Class\\Name\\Never\\Create\\A\Class\\With\This\\Name';

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertEquals('no_page', $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests rendering the Contao 404 page with a response exception.
     */
    public function testTryToRenderContao404ThrowsResponseException()
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new NotFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $thrownException = new ResponseException('internal 404 response exception', 404);

        PageError404::$getResponse = function () use ($thrownException) { throw $thrownException; };

        $GLOBALS['TL_PTY']['error_404'] = 'PageError404';

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertEquals(404, $event->getResponse()->getStatusCode());
        $this->assertEquals($thrownException->getResponse(), $event->getResponse());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests rendering the Contao 404 page with a page not found exception.
     */
    public function testTryToRenderContao404ThrowsNotFoundHttpException()
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new NotFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $thrownException = new NotFoundHttpException('internal 404 response exception');

        PageError404::$getResponse = function () use ($thrownException) { throw $thrownException; };

        $GLOBALS['TL_PTY']['error_404'] = 'PageError404';

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());
        $this->assertEquals(404, $event->getResponse()->getStatusCode());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );
    }

    /**
     * Tests rendering the Contao 404 page with an exception.
     */
    public function testTryToRenderContao404ThrowsException()
    {
        $this->microBootFramework();

        $listener  = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception = new NotFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $thrownException = new \RuntimeException(
            'This should be thrown as it indicates a bug in exception handling.'
        );

        PageError404::$getResponse = function () use ($thrownException) { throw $thrownException; };

        $GLOBALS['TL_PTY']['error_404'] = 'PageError404';

        $realThrownException = null;
        try {
            $listener->onKernelException($event);
        } catch (\Exception $realThrownException) {
            // Just to keep the exception.
        }

        $this->assertSame($thrownException, $realThrownException);
        $this->assertNull($event->getResponse());
    }

    /**
     * Tests rendering the Contao 404 page with recursion.
     */
    public function testTryToRenderContao404WillRecurse()
    {
        $this->microBootFramework();

        $kernel     = $this->mockKernel();
        $listener   = new ExceptionListener(true, $this->mockTwig(), $this->mockConfig());
        $exception  = new NotFoundHttpException();
        $event      = new GetResponseForExceptionEvent($kernel, new Request(), HttpKernel::MASTER_REQUEST, $exception);
        $eventAgain = new GetResponseForExceptionEvent($kernel, new Request(), HttpKernel::MASTER_REQUEST, $exception);

        PageError404::$getResponse = function () use ($listener, $eventAgain) {
            $listener->onKernelException($eventAgain);
        };

        $GLOBALS['TL_PTY']['error_404'] = 'PageError404';

        try {
            $listener->onKernelException($event);
        } catch (\Exception $thrownException) {
            // Just to keep the exception.
        }
    }

    /**
     * Tests the fallback to the generic error screen when no language strings are available.
     *
     * @runInSeparateProcess
     */
    public function testErrorWithoutLanguageStringsUsesLastResort()
    {
        /** @var ExceptionListener $listener */
        $listener = $this->getMock(
            'Contao\CoreBundle\EventListener\ExceptionListener',
            ['loadLanguageStrings'],
            [true, $this->mockTwig(), $this->mockConfig()]
        );

        /** @var \Exception $exception */
        $exception = new NoPagesFoundHttpException();

        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernel::MASTER_REQUEST,
            $exception
        );

        $listener->onKernelException($event);

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Response', $event->getResponse());

        $this->assertEquals('error', $event->getResponse()->getContent());
        $this->assertTrue($event->getResponse()->headers->has('X-Status-Code'));

        $this->assertEquals(
            $event->getResponse()->getStatusCode(),
            $event->getResponse()->headers->get('X-Status-Code')
        );

        $this->assertEquals(500, $event->getResponse()->getStatusCode());
    }

    /**
     * Mocks the Twig engine.
     *
     * @return TwigEngine The Twig engine
     */
    private function mockTwig($templates = null)
    {
        if (null === $templates) {
            $reflection = new \ReflectionProperty(
                'Contao\CoreBundle\EventListener\ExceptionListener',
                'exceptionTemplates'
            );

            $reflection->setAccessible(true);
            $templates = array_merge(['error'], array_values($reflection->getValue()));
        }

        $templateMap = [];

        foreach ($templates as $template) {
            $templateMap["@ContaoCore/Error/$template.html.twig"] = $template;
        }

        $twig = $this
            ->getMockBuilder('Symfony\\Bundle\\TwigBundle\\TwigEngine')
            ->disableOriginalConstructor()
            ->setMethods(['exists', 'renderResponse'])
            ->getMock()
        ;

        $twig
            ->expects($this->any())
            ->method('exists')
            ->willReturnCallback(function ($template) use ($templateMap) {
                return isset($templateMap[$template]);
            })
        ;

        $twig
            ->expects($this->any())
            ->method('renderResponse')
            ->willReturnCallback(function ($template) use ($templateMap) {
                if (!isset($templateMap[$template])) {
                    throw new \InvalidArgumentException('invalid template name');
                }

                return new Response($templateMap[$template]);
            })
        ;

        return $twig;
    }

    /**
     * Provides the data for testing all known Contao exceptions.
     */
    public function knownContaoExceptions()
    {
        $reflection = new \ReflectionProperty(
            'Contao\CoreBundle\EventListener\ExceptionListener',
            'exceptionTemplates'
        );

        $reflection->setAccessible(true);

        $classMap = $reflection->getValue();

        return array_map(function ($class) use ($classMap) { return [$class, $classMap[$class]]; }, array_keys($classMap));
    }

    /**
     * Defines the constants needed by the 404 page handler.
     */
    private function microBootFramework()
    {
        if (!defined('BE_USER_LOGGED_IN')) {
            define('BE_USER_LOGGED_IN', false);
        }
    }
}
