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

use Contao\CoreBundle\EventListener\PreviewAuthenticationListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class PreviewAuthenticationListenerTest extends TestCase
{
    public function testDoesNothingIfPreviewAttributeIsNotSet(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $tokenChecker = $this->createMock(TokenChecker::class);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $uriSigner = $this->createMock(UriSigner::class);

        $requestEvent = $this->getRequestEvent();

        $listener = new PreviewAuthenticationListener($scopeMatcher, $tokenChecker, $router, $uriSigner);
        $listener($requestEvent);

        $this->assertNull($requestEvent->getResponse());
    }

    public function testRedirectsToLoginIfNoBackendUserIsAuthenticated(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('canAccessPreview')
            ->willReturn(false)
        ;

        $context = $this->createMock(RequestContext::class);
        $context
            ->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('')
        ;

        $context
            ->expects($this->exactly(2))
            ->method('setBaseUrl')
        ;

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($context)
        ;

        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login')
            ->willReturn('/contao/login')
        ;

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->once())
            ->method('sign')
            ->with('/contao/login')
            ->willReturn('/contao/login')
        ;

        $request = new Request([], [], [], [], [], ['SCRIPT_NAME' => '/preview.php']);
        $request->attributes->set('_preview', true);

        $requestEvent = $this->getRequestEvent($request);

        $listener = new PreviewAuthenticationListener($scopeMatcher, $tokenChecker, $router, $uriSigner);
        $listener($requestEvent);

        $this->assertInstanceOf(RedirectResponse::class, $requestEvent->getResponse());
    }

    private function getRequestEvent(Request|null $request = null): RequestEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        return new RequestEvent($kernel, $request ?? new Request(), HttpKernelInterface::MAIN_REQUEST);
    }
}
