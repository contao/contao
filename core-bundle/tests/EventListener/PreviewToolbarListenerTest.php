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
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class PreviewToolbarListenerTest extends TestCase
{
    /**
     * @dataProvider getInjectToolbarData
     */
    public function testInjectsTheToolbarBeforeTheClosingBodyTag($content, $expected): void
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

    public function getInjectToolbarData(): \Generator
    {
        yield [
            '<html><head></head><body></body></html>',
            "<html><head></head><body>\nCONTAO\n</body></html>",
        ];

        yield [
            '<html><head></head><body><textarea><html><head></head><body></body></html></textarea></body></html>',
            "<html><head></head><body><textarea><html><head></head><body></body></html></textarea>\nCONTAO\n</body></html>",
        ];
    }

    public function testInjectsTheToolbarIntoTheResponse(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
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

        $listener($event);

        $this->assertSame("<html><head></head><body>\nCONTAO\n</body></html>", $response->getContent());
    }

    public function testDoesNotInjectTheToolbarIfThereIsNoPreviewEntrypoint(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->getRequestMock(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new PreviewToolbarListener(
            '',
            $this->mockScopeMatcher(),
            $this->getTwigMock(),
            $this->mockRouterWithContext()
        );

        $listener($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    public function testDoesNotInjectTheToolbarIfTheContentTypeIsNotHtml(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Type', 'text/xml');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
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

        $listener($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    public function testDoesNotInjectTheToolbarOnContentDispositionAttachment(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Disposition', 'attachment; filename=test.html');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
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

        $listener($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    /**
     * @dataProvider getRedirects
     */
    public function testDoesNotInjectTheToolbarIntoARedirectResponse(int $statusCode, bool $hasSession): void
    {
        $response = new Response('<html><head></head><body></body></html>', $statusCode);

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
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

        $listener($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    public function getRedirects(): \Generator
    {
        yield [301, true];
        yield [302, true];
        yield [301, false];
        yield [302, false];
    }

    public function testDoesNotInjectTheToolbarIntoAnIncompleteHtmlResponse(): void
    {
        $response = new Response('<div>Some content</div>');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
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

        $listener($event);

        $this->assertSame('<div>Some content</div>', $response->getContent());
    }

    public function testDoesNotInjectTheToolbarUponXmlHttpRequests(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
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

        $listener($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    public function testDoesNotInjectTheToolbarUponNonHtmlRequests(): void
    {
        $response = new Response('<html><head></head><body></body></html>');
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
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

        $listener($event);

        $this->assertSame('<html><head></head><body></body></html>', $response->getContent());
    }

    /**
     * @return Request&MockObject
     */
    protected function getRequestMock(bool $isXmlHttpRequest = false, string $requestFormat = 'html', bool $hasSession = true): Request
    {
        $request = $this->createMock(Request::class);
        $request->headers = new HeaderBag();

        $request
            ->method('isXmlHttpRequest')
            ->willReturn($isXmlHttpRequest)
        ;

        $request
            ->method('getRequestFormat')
            ->willReturn($requestFormat)
        ;

        $request
            ->method('getScriptName')
            ->willReturn('preview.php')
        ;

        if ($hasSession) {
            $request->setSession($this->createMock(Session::class));
        }

        return $request;
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

    /**
     * @return Environment&MockObject
     */
    private function getTwigMock(string $render = 'CONTAO'): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->method('render')
            ->willReturn($render)
        ;

        return $twig;
    }

    /**
     * @return RouterInterface&MockObject
     */
    private function mockRouterWithContext(array $expectedParameters = [], string $expectedRoute = 'contao_backend_preview_switch', int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): RouterInterface
    {
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
