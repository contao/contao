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
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
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

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false)
        ;

        $exception = new InternalServerErrorHttpException('', new InternalServerErrorException());
        $event = $this->getResponseEvent($exception);

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $this->createMock(FragmentRendererInterface::class));
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(500, $event->getResponse()->getStatusCode());
    }

    public function testCatchesAuthenticationCredentialsNotFoundExceptionWhenRenderingBackEndExceptions(): void
    {
        $twig = $this->createMock(Environment::class);
        $framework = $this->mockContaoFramework();

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->willThrowException(new AuthenticationCredentialsNotFoundException())
        ;

        $exception = new InternalServerErrorHttpException('', new InternalServerErrorException());
        $event = $this->getResponseEvent($exception);

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $this->createMock(FragmentRendererInterface::class));
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(500, $event->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider getErrorTypes
     */
    public function testRendersTheErrorPageFragment(int $type, \Exception $exception): void
    {
        $currentPage = $this->mockClassWithProperties(PageModel::class, ['rootId' => 17]);

        $request = $this->getRequest('frontend');
        $request->attributes->set('pageModel', $currentPage);

        $event = $this->getResponseEvent($exception, $request);

        $pageModel = $this->createMock(PageModel::class);
        $pageModel
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('http://example.com/foobar.html')
        ;

        $pageAdapter = $this->mockAdapter(['findFirstOfTypeByPid']);
        $pageAdapter
            ->expects($this->once())
            ->method('findFirstOfTypeByPid')
            ->with('error_'.$type, 17)
            ->willReturn($pageModel)
        ;

        $response = $this->createMock(Response::class);

        $fragmentRenderer = $this->createMock(FragmentRendererInterface::class);
        $fragmentRenderer
            ->expects($this->once())
            ->method('render')
            ->with('http://example.com/foobar.html')
            ->willReturn($response)
        ;

        $listener = new PrettyErrorScreenListener(
            true,
            $this->createMock(Environment::class),
            $this->mockContaoFramework([PageModel::class => $pageAdapter]),
            $this->createMock(Security::class),
            $fragmentRenderer
        );

        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame($response, $event->getResponse());
    }

    public function getErrorTypes(): \Generator
    {
        yield [401, new UnauthorizedHttpException('', '', new InsufficientAuthenticationException())];
        yield [403, new AccessDeniedHttpException('', new AccessDeniedException())];
        yield [404, new NotFoundHttpException('', new PageNotFoundException())];
    }

    public function testHandlesResponseExceptionsWhenRenderingPageFragment(): void
    {
        $currentPage = $this->mockClassWithProperties(PageModel::class, ['rootId' => 17]);

        $request = $this->getRequest('frontend');
        $request->attributes->set('pageModel', $currentPage);

        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $event = $this->getResponseEvent($exception, $request);

        $pageModel = $this->createMock(PageModel::class);
        $pageModel
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('http://example.com/foobar.html')
        ;

        $pageAdapter = $this->mockAdapter(['findFirstOfTypeByPid']);
        $pageAdapter
            ->expects($this->once())
            ->method('findFirstOfTypeByPid')
            ->with('error_403', 17)
            ->willReturn($pageModel)
        ;

        $response = $this->createMock(Response::class);

        $fragmentRenderer = $this->createMock(FragmentRendererInterface::class);
        $fragmentRenderer
            ->expects($this->once())
            ->method('render')
            ->with('http://example.com/foobar.html')
            ->willThrowException(new ResponseException($response))
        ;

        $listener = new PrettyErrorScreenListener(
            true,
            $this->createMock(Environment::class),
            $this->mockContaoFramework([PageModel::class => $pageAdapter]),
            $this->createMock(Security::class),
            $fragmentRenderer
        );

        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame($response, $event->getResponse());
    }

    public function testDoesNotAddPageResponseOnException(): void
    {
        $currentPage = $this->mockClassWithProperties(PageModel::class, ['rootId' => 17]);

        $request = $this->getRequest('frontend');
        $request->attributes->set('pageModel', $currentPage);

        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $event = $this->getResponseEvent($exception, $request);

        $pageModel = $this->createMock(PageModel::class);
        $pageModel
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('http://example.com/foobar.html')
        ;

        $pageAdapter = $this->mockAdapter(['findFirstOfTypeByPid']);
        $pageAdapter
            ->expects($this->once())
            ->method('findFirstOfTypeByPid')
            ->with('error_403', 17)
            ->willReturn($pageModel)
        ;

        $fragmentRenderer = $this->createMock(FragmentRendererInterface::class);
        $fragmentRenderer
            ->expects($this->once())
            ->method('render')
            ->with('http://example.com/foobar.html')
            ->willThrowException(new \Exception())
        ;

        $listener = new PrettyErrorScreenListener(
            true,
            $this->createMock(Environment::class),
            $this->mockContaoFramework([PageModel::class => $pageAdapter]),
            $this->createMock(Security::class),
            $fragmentRenderer
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }



    public function testDoesNotAddResponseIfNoPageIsFoundInRoot(): void
    {
        $currentPage = $this->mockClassWithProperties(PageModel::class, ['rootId' => 17]);

        $request = $this->getRequest('frontend');
        $request->attributes->set('pageModel', $currentPage);

        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $event = $this->getResponseEvent($exception, $request);

        $pageAdapter = $this->mockAdapter(['findFirstOfTypeByPid']);
        $pageAdapter
            ->expects($this->once())
            ->method('findFirstOfTypeByPid')
            ->with('error_403', 17)
            ->willReturn(null)
        ;

        $response = $this->createMock(Response::class);

        $fragmentRenderer = $this->createMock(FragmentRendererInterface::class);
        $fragmentRenderer
            ->expects($this->never())
            ->method('render')
        ;

        $listener = new PrettyErrorScreenListener(
            true,
            $this->createMock(Environment::class),
            $this->mockContaoFramework([PageModel::class => $pageAdapter]),
            $this->createMock(Security::class),
            $fragmentRenderer
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNotRenderFragmentWithoutCurrentRequestPage(): void
    {
        $request = $this->getRequest('frontend');
        $request->attributes->remove('pageModel');

        $exception = new AccessDeniedHttpException('', new AccessDeniedException());
        $event = $this->getResponseEvent($exception, $request);

        $pageModel = $this->createMock(PageModel::class);
        $pageModel
            ->expects($this->never())
            ->method('getAbsoluteUrl')
        ;

        $pageAdapter = $this->mockAdapter(['findFirstOfTypeByPid']);
        $pageAdapter
            ->expects($this->never())
            ->method('findFirstOfTypeByPid')
        ;

        $fragmentRenderer = $this->createMock(FragmentRendererInterface::class);
        $fragmentRenderer
            ->expects($this->never())
            ->method('render')
        ;

        $listener = new PrettyErrorScreenListener(
            true,
            $this->createMock(Environment::class),
            $this->mockContaoFramework([PageModel::class => $pageAdapter]),
            $this->createMock(Security::class),
            $fragmentRenderer
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testRendersServiceUnavailableHttpExceptions(): void
    {
        $exception = new ServiceUnavailableHttpException(null, null, new ServiceUnavailableException());
        $event = $this->getResponseEvent($exception, $this->getRequest('frontend'));

        $listener = $this->getListener();
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(503, $event->getResponse()->getStatusCode());
    }

    public function testDoesNotRenderExceptionsIfDisabled(): void
    {
        $exception = new ServiceUnavailableHttpException(null, null, new ServiceUnavailableException());
        $event = $this->getResponseEvent($exception, $this->getRequest('frontend'));

        $twig = $this->createMock(Environment::class);
        $framework = $this->mockContaoFramework();

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false)
        ;

        $listener = new PrettyErrorScreenListener(false, $twig, $framework, $security, $this->createMock(FragmentRendererInterface::class));
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNotRenderExceptionsUponSubrequests(): void
    {
        $twig = $this->createMock(Environment::class);
        $framework = $this->mockContaoFramework();

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $exception = new ServiceUnavailableHttpException(null, null, new ServiceUnavailableException());
        $event = $this->getResponseEvent($exception, null, true);

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $this->createMock(FragmentRendererInterface::class));
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testRendersUnknownHttpExceptions(): void
    {
        $event = $this->getResponseEvent(new ConflictHttpException(), $this->getRequest('frontend'));

        $listener = $this->getListener(false);
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
                static function () use (&$count): void {
                    if (0 === $count++) {
                        throw new Error('foo');
                    }
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

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $exception = new InternalServerErrorHttpException('', new InsecureInstallationException());
        $event = $this->getResponseEvent($exception, $this->getRequest('frontend', 'json'));

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $this->createMock(FragmentRendererInterface::class));
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

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $exception = new InternalServerErrorHttpException('', new InsecureInstallationException());
        $event = $this->getResponseEvent($exception, $this->getRequest('backend', 'html', 'application/json'));

        $listener = new PrettyErrorScreenListener(true, $twig, $framework, $security, $this->createMock(FragmentRendererInterface::class));
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

    private function getListener(bool $isBackendUser = false, Environment $twig = null): PrettyErrorScreenListener
    {
        if (null === $twig) {
            $twig = $this->createMock(Environment::class);
        }

        $framework = $this->mockContaoFramework();

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn($isBackendUser)
        ;

        $fragmentRenderer = $this->createMock(FragmentRendererInterface::class);

        return new PrettyErrorScreenListener(true, $twig, $framework, $security, $fragmentRenderer);
    }

    private function getRequest(string $scope = 'backend', string $format = 'html', string $accept = 'text/html'): Request
    {
        $request = new Request();
        $request->attributes->set('_scope', $scope);
        $request->attributes->set('_format', $format);
        $request->headers->set('Accept', $accept);

        return $request;
    }

    private function getResponseEvent(\Exception $exception, Request $request = null, bool $isSubRequest = false): ExceptionEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = $this->getRequest();
        }

        $type = $isSubRequest ? HttpKernelInterface::SUB_REQUEST : HttpKernelInterface::MASTER_REQUEST;

        return new ExceptionEvent($kernel, $request, $type, $exception);
    }
}
