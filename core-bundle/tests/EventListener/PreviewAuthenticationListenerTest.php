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

class PreviewAuthenticationListenerTest extends TestCase
{
    public function testReturnsIfNoPreviewScriptIsSet(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $tokenChecker = $this->createMock(TokenChecker::class);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $uriSigner = $this->createMock(UriSigner::class);
        $previewScript = '';

        $requestEvent = $this->getRequestEvent();

        $listener = new PreviewAuthenticationListener($scopeMatcher, $tokenChecker, $router, $uriSigner, $previewScript);
        $listener($requestEvent);

        $this->assertNull($requestEvent->getResponse());
    }

    public function testRedirectsToLoginIfNoBackendUserIsAuthenticated(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true)
        ;

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('hasBackendUser')
            ->willReturn(false)
        ;

        $router = $this->createMock(UrlGeneratorInterface::class);
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
            ->willReturn('/contao/login')
        ;

        $previewScript = '/preview.php';

        $request = new Request([], [], [], [], [], ['SCRIPT_NAME' => '/preview.php']);

        $requestEvent = $this->getRequestEvent($request);

        $listener = new PreviewAuthenticationListener($scopeMatcher, $tokenChecker, $router, $uriSigner, $previewScript);
        $listener($requestEvent);

        $this->assertInstanceOf(RedirectResponse::class, $requestEvent->getResponse());
    }

    private function getRequestEvent(Request $request = null, bool $isSubRequest = false): RequestEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = new Request();
        }

        $type = $isSubRequest ? HttpKernelInterface::SUB_REQUEST : HttpKernelInterface::MASTER_REQUEST;

        return new RequestEvent($kernel, $request, $type);
    }
}
