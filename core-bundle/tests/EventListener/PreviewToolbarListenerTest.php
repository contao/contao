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

use Contao\CoreBundle\EventListener\PreviewToolbarListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class PreviewToolbarListenerTest extends TestCase
{
    /**
     * @dataProvider getInjectToolbarTests
     */
    public function testInjectToolbar($content, $expected): void
    {
        $listener = new PreviewToolbarListener(
            'preview.php',
            $this->mockScopeMatcher(),
            $this->getTwigMock(),
            $this->mockRouterWithContext()
        );
        $m = new \ReflectionMethod($listener, 'injectToolbar');
        $m->setAccessible(true);

        $response = new Response($content);

        $m->invoke($listener, $response, Request::create('/'));
        $this->assertSame($expected, $response->getContent());
    }

    public function getInjectToolbarTests()
    {
        return [
            ['<html><head></head><body></body></html>', "<html><head></head><body>\nCONTAO\n</body></html>"],
            [
                '<html>
            <head></head>
            <body>
            <textarea><html><head></head><body></body></html></textarea>
            </body>
            </html>',
                "<html>
            <head></head>
            <body>
            <textarea><html><head></head><body></body></html></textarea>
            \nCONTAO\n</body>
            </html>",
            ],
        ];
    }

    public function testToolbarIsInjected(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        $event = new ResponseEvent(
            $this->getKernelMock(),
            $this->getRequestMock(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new PreviewToolbarListener(
            'preview.php',
            $this->mockScopeMatcher(),
            $this->getTwigMock(),
            $this->mockRouterWithContext()
        );
        $listener->onKernelResponse($event);

        $this->assertSame("<html><head></head><body>\nCONTAO\n</body></html>", $response->getContent());
    }

    /**
     * @depends testToolbarIsInjected
     */
    public function testToolbarIsNotInjectedOnNonHtmlContentType(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Type', 'text/xml');
        $event = new ResponseEvent(
            $this->getKernelMock(),
            $this->getRequestMock(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new PreviewToolbarListener(
            'preview.php',
            $this->mockScopeMatcher(),
            $this->getTwigMock(),
            $this->mockRouterWithContext()
        );
        $listener->onKernelResponse($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    /**
     * @depends testToolbarIsInjected
     */
    public function testToolbarIsNotInjectedOnContentDispositionAttachment(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Disposition', 'attachment; filename=test.html');
        $event = new ResponseEvent(
            $this->getKernelMock(),
            $this->getRequestMock(false, 'html'),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new PreviewToolbarListener(
            'preview.php',
            $this->mockScopeMatcher(),
            $this->getTwigMock(),
            $this->mockRouterWithContext()
        );
        $listener->onKernelResponse($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    /**
     * @depends      testToolbarIsInjected
     * @dataProvider provideRedirects
     */
    public function testToolbarIsNotInjectedOnRedirection($statusCode, $hasSession): void
    {
        $response = new Response('<html><head></head><body></body></html>', $statusCode);
        $event = new ResponseEvent(
            $this->getKernelMock(),
            $this->getRequestMock(false, 'html', $hasSession),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );
        $listener = new PreviewToolbarListener(
            'preview.php',
            $this->mockScopeMatcher(),
            $this->getTwigMock(),
            $this->mockRouterWithContext()
        );
        $listener->onKernelResponse($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    public function provideRedirects()
    {
        return [
            [301, true],
            [302, true],
            [301, false],
            [302, false],
        ];
    }

    /**
     * @depends testToolbarIsInjected
     */
    public function testToolbarIsNotInjectedOnIncompleteHtmlResponses(): void
    {
        $response = new Response('<div>Some content</div>');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        $event = new ResponseEvent(
            $this->getKernelMock(),
            $this->getRequestMock(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new PreviewToolbarListener(
            'preview.php',
            $this->mockScopeMatcher(),
            $this->getTwigMock(),
            $this->mockRouterWithContext()
        );
        $listener->onKernelResponse($event);

        $this->assertSame('<div>Some content</div>', $response->getContent());
    }

    /**
     * @depends testToolbarIsInjected
     */
    public function testToolbarIsNotInjectedOnXmlHttpRequests(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        $event = new ResponseEvent(
            $this->getKernelMock(),
            $this->getRequestMock(true),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new PreviewToolbarListener(
            'preview.php',
            $this->mockScopeMatcher(),
            $this->getTwigMock(),
            $this->mockRouterWithContext()
        );
        $listener->onKernelResponse($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    /**
     * @depends testToolbarIsInjected
     */
    public function testToolbarIsNotInjectedOnNonHtmlRequests(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        $event = new ResponseEvent(
            $this->getKernelMock(),
            $this->getRequestMock(false, 'json'),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new PreviewToolbarListener(
            'preview.php',
            $this->mockScopeMatcher(),
            $this->getTwigMock(),
            $this->mockRouterWithContext()
        );
        $listener->onKernelResponse($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    protected function getRequestMock($isXmlHttpRequest = false, $requestFormat = 'html', $hasSession = true)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->setMethods(
            ['getSession', 'isXmlHttpRequest', 'getRequestFormat', 'getScriptName']
        )->disableOriginalConstructor()->getMock();
        $request->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn($isXmlHttpRequest)
        ;
        $request->expects($this->any())
            ->method('getRequestFormat')
            ->willReturn($requestFormat)
        ;

        $request->expects($this->any())
            ->method('getScriptName')
            ->willReturn('preview.php')
        ;

        $request->headers = new HeaderBag();

        if ($hasSession) {
            $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
                ->disableOriginalConstructor()
                ->getMock()
            ;
            $request->expects($this->any())
                ->method('getSession')
                ->willReturn($session)
            ;
        }

        return $request;
    }

    protected function getTwigMock($render = 'CONTAO')
    {
        $templating = $this->getMockBuilder('Twig\Environment')->disableOriginalConstructor()->getMock();
        $templating->expects($this->any())
            ->method('render')
            ->willReturn($render)
        ;

        return $templating;
    }

    protected function getKernelMock()
    {
        return $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')->disableOriginalConstructor()->getMock();
    }

    /**
     * @return ScopeMatcher&MockObject
     */
    protected function mockScopeMatcher(): ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(true)
        ;

        return $scopeMatcher;
    }

    private function mockRouterWithContext(
        array $expectedParameters = [],
        string $expectedRoute = 'contao_backend_preview_switch',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): UrlGeneratorInterface {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->with($expectedRoute, $expectedParameters, $referenceType)
        ;

        $router
            ->method('getContext')
            ->willReturn(new RequestContext())
        ;

        return $router;
    }
}
