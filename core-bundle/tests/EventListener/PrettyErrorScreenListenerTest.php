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

use Contao\CoreBundle\EventListener\PrettyErrorScreenListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Exception\ServiceUnavailableException;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;
use Twig\Error\Error;

class PrettyErrorScreenListenerTest extends TestCase
{
    public function testRendersBackEndExceptions(): void
    {
        $exception = new InternalServerErrorHttpException('', new InternalServerErrorException());
        $event = $this->getResponseEvent($exception);

        $listener = $this->getListener(true);
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(500, $event->getResponse()->getStatusCode());
    }

    public function testChecksIsGrantedBeforeRenderingBackEndExceptions(): void
    {
        $twig = $this->createMock(Environment::class);
        $framework = $this->mockContaoFramework();
        $pageRegistry = $this->createMock(PageRegistry::class);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $pageFinder = $this->createMock(PageFinder::class);

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false)
        ;

        $exception = new InternalServerErrorHttpException('', new InternalServerErrorException());
        $event = $this->getResponseEvent($exception);

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $pageRegistry, $httpKernel, $pageFinder);
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(500, $event->getResponse()->getStatusCode());
    }

    public function testCatchesAuthenticationCredentialsNotFoundExceptionWhenRenderingBackEndExceptions(): void
    {
        $twig = $this->createMock(Environment::class);
        $framework = $this->mockContaoFramework();
        $pageRegistry = $this->createMock(PageRegistry::class);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $pageFinder = $this->createMock(PageFinder::class);

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->willThrowException(new AuthenticationCredentialsNotFoundException())
        ;

        $exception = new InternalServerErrorHttpException('', new InternalServerErrorException());
        $event = $this->getResponseEvent($exception);

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $pageRegistry, $httpKernel, $pageFinder);
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(500, $event->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider getErrorTypes
     */
    public function testCreatesSubrequestForException(int $type, \Exception $exception): void
    {
        $errorPage = $this->mockPageWithProperties([
            'pid' => 1,
            'type' => 'error_'.$type,
            'rootLanguage' => '',
        ]);

        $request = $this->getRequest('frontend');
        $request->attributes->set('pageModel', $this->mockPageWithProperties(['rootId' => 1]));

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $httpKernel
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new Response('foo', $type))
        ;

        $event = $this->getResponseEvent($exception, $request);

        $listener = $this->getListener(false, null, $errorPage, $httpKernel);
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame($type, $event->getResponse()->getStatusCode());
    }

    public function getErrorTypes(): \Generator
    {
        yield [401, new UnauthorizedHttpException('', '', new InsufficientAuthenticationException())];
        yield [403, new AccessDeniedHttpException('', new AccessDeniedException())];
        yield [404, new NotFoundHttpException('', new PageNotFoundException())];
    }

    public function testUnprotectsErrorPage(): void
    {
        $errorPage = $this->mockPageWithProperties([
            'pid' => 1,
            'type' => 'error_401',
            'rootLanguage' => '',
            'protected' => true,
            'groups' => '',
        ]);

        $request = $this->getRequest('frontend');
        $request->attributes->set('pageModel', $this->mockPageWithProperties(['rootId' => 1]));

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $httpKernel
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new Response('foo', 401))
        ;

        $exception = new UnauthorizedHttpException('', '', new InsufficientAuthenticationException());
        $event = $this->getResponseEvent($exception, $request);

        $listener = $this->getListener(false, null, $errorPage, $httpKernel);
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(401, $event->getResponse()->getStatusCode());
        $this->assertFalse($errorPage->protected);
    }

    public function testHandlesResponseExceptionsWhenForwarding(): void
    {
        $errorPage = $this->mockPageWithProperties([
            'pid' => 1,
            'type' => 'error_403',
            'rootLanguage' => '',
        ]);

        $request = $this->getRequest('frontend');
        $request->attributes->set('pageModel', $this->mockPageWithProperties(['rootId' => 1]));

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $httpKernel
            ->expects($this->once())
            ->method('handle')
            ->willThrowException(new ResponseException(new Response('foo')))
        ;

        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $event = $this->getResponseEvent($exception, $request);

        $listener = $this->getListener(false, null, $errorPage, $httpKernel);
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame('foo', $event->getResponse()->getContent());
    }

    public function testReplacesThrowableWhenForwarding(): void
    {
        $errorPage = $this->mockPageWithProperties([
            'pid' => 1,
            'type' => 'error_403',
            'rootLanguage' => '',
        ]);

        $request = $this->getRequest('frontend');
        $request->attributes->set('pageModel', $this->mockPageWithProperties(['rootId' => 1]));

        $throwable = new \RuntimeException();

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $httpKernel
            ->expects($this->once())
            ->method('handle')
            ->willThrowException($throwable)
        ;

        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $event = $this->getResponseEvent($exception, $request);

        $listener = $this->getListener(false, null, $errorPage, $httpKernel);
        $listener($event);

        $this->assertFalse($event->hasResponse());
        $this->assertSame($throwable, $event->getThrowable());
    }

    public function testRendersServiceUnavailableHttpExceptions(): void
    {
        $exception = new ServiceUnavailableHttpException(null, '', new ServiceUnavailableException(''));
        $event = $this->getResponseEvent($exception, $this->getRequest('frontend'));

        $listener = $this->getListener();
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(503, $event->getResponse()->getStatusCode());
    }

    public function testDoesNotRenderExceptionsIfDisabled(): void
    {
        $exception = new ServiceUnavailableHttpException(null, '', new ServiceUnavailableException(''));
        $event = $this->getResponseEvent($exception, $this->getRequest('frontend'));

        $twig = $this->createMock(Environment::class);
        $framework = $this->mockContaoFramework();
        $pageRegistry = $this->createMock(PageRegistry::class);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $pageFinder = $this->createMock(PageFinder::class);

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false)
        ;

        $listener = new PrettyErrorScreenListener(false, $twig, $framework, $security, $pageRegistry, $httpKernel, $pageFinder);
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNotRenderExceptionsUponSubrequests(): void
    {
        $twig = $this->createMock(Environment::class);
        $framework = $this->mockContaoFramework();
        $pageRegistry = $this->createMock(PageRegistry::class);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $pageFinder = $this->createMock(PageFinder::class);

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $exception = new ServiceUnavailableHttpException(null, '', new ServiceUnavailableException(''));
        $event = $this->getResponseEvent($exception, null, true);

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $pageRegistry, $httpKernel, $pageFinder);
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testRendersUnknownHttpExceptions(): void
    {
        $event = $this->getResponseEvent(new ConflictHttpException(), $this->getRequest('frontend'));

        $listener = $this->getListener();
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(409, $event->getResponse()->getStatusCode());
    }

    public function testRendersTheErrorScreen(): void
    {
        $exception = new InternalServerErrorHttpException('', new ForwardPageNotFoundException());
        $event = $this->getResponseEvent($exception, $this->getRequest('frontend'));
        $twig = $this->createMock(Environment::class);
        $count = 0;

        $twig
            ->method('render')
            ->willReturnCallback(
                static function () use (&$count): string {
                    if (0 === $count++) {
                        throw new Error('foo');
                    }

                    return '';
                }
            )
        ;

        $listener = $this->getListener(false, $twig);
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(500, $event->getResponse()->getStatusCode());
    }

    public function testDoesNothingIfTheFormatIsNotHtml(): void
    {
        $twig = $this->createMock(Environment::class);
        $framework = $this->mockContaoFramework();
        $pageRegistry = $this->createMock(PageRegistry::class);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $pageFinder = $this->createMock(PageFinder::class);

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $exception = new InternalServerErrorHttpException('', new InsecureInstallationException());
        $event = $this->getResponseEvent($exception, $this->getRequest('frontend', 'json'));

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $pageRegistry, $httpKernel, $pageFinder);
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Tests that the listener is bypassed if text/html is not accepted.
     */
    public function testDoesNothingIfTextHtmlIsNotAccepted(): void
    {
        $twig = $this->createMock(Environment::class);
        $framework = $this->mockContaoFramework();
        $pageRegistry = $this->createMock(PageRegistry::class);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $pageFinder = $this->createMock(PageFinder::class);

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $exception = new InternalServerErrorHttpException('', new InsecureInstallationException());
        $event = $this->getResponseEvent($exception, $this->getRequest('backend', 'html', 'application/json'));

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $pageRegistry, $httpKernel, $pageFinder);
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNothingIfThePageHandlerDoesNotExist(): void
    {
        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $event = $this->getResponseEvent($exception);

        $listener = $this->getListener();
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNotLogUnloggableExceptions(): void
    {
        $exception = new InternalServerErrorHttpException('', new InsecureInstallationException());
        $event = $this->getResponseEvent($exception);

        $listener = $this->getListener();
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(500, $event->getResponse()->getStatusCode());
    }

    private function getListener(bool $isBackendUser = false, Environment|null $twig = null, PageModel|null $errorPage = null, HttpKernelInterface|null $httpKernel = null): PrettyErrorScreenListener
    {
        $twig ??= $this->createMock(Environment::class);
        $httpKernel ??= $this->createMock(HttpKernelInterface::class);

        $pageFinder = $this->createMock(PageFinder::class);
        $pageRegistry = $this->createMock(PageRegistry::class);

        if (null !== $errorPage) {
            $pageFinder
                ->expects($this->once())
                ->method('findFirstPageOfTypeForRequest')
                ->with($this->isInstanceOf(Request::class), $errorPage->type)
                ->willReturn($errorPage)
            ;

            $pageRegistry
                ->expects($this->once())
                ->method('getRoute')
                ->with($errorPage)
                ->willReturn(new PageRoute($errorPage))
            ;
        }

        $framework = $this->mockContaoFramework();

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn($isBackendUser)
        ;

        return new PrettyErrorScreenListener(true, $twig, $framework, $security, $pageRegistry, $httpKernel, $pageFinder);
    }

    private function getRequest(string $scope = 'backend', string $format = 'html', string $accept = 'text/html'): Request
    {
        $request = new Request();
        $request->headers->set('Accept', $accept);

        $request->attributes->set('_scope', $scope);
        $request->attributes->set('_format', $format);

        return $request;
    }

    private function getResponseEvent(\Exception $exception, Request|null $request = null, bool $isSubRequest = false): ExceptionEvent
    {
        $kernel = $this->createMock(KernelInterface::class);
        $type = $isSubRequest ? HttpKernelInterface::SUB_REQUEST : HttpKernelInterface::MAIN_REQUEST;

        return new ExceptionEvent($kernel, $request ?? $this->getRequest(), $type, $exception);
    }

    /**
     * @return PageModel&MockObject
     */
    private function mockPageWithProperties(array $properties = []): PageModel
    {
        $page = $this->mockClassWithProperties(PageModel::class, $properties);
        $page
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        return $page;
    }
}
