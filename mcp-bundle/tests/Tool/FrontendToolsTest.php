<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Tool;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\McpBundle\Tool\FrontendTools;
use Contao\TestCase\ContaoTestCase;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class FrontendToolsTest extends ContaoTestCase
{
    public function testInspectsAPageInPreviewModeAndRestoresTheSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(FrontendPreviewAuthenticator::SESSION_NAME, ['showUnpublished' => false]);
        $stack = new RequestStack();
        $request = Request::create('https://example.org/contao/mcp');
        $request->setSession($session);
        $stack->push($request);

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            ContaoCoreEvents::PREVIEW_URL_CONVERT,
            static function (PreviewUrlConvertEvent $event): void {
                $event->setUrl('https://example.org/example.html');
            },
        );

        $authenticator = $this->createMock(FrontendPreviewAuthenticator::class);
        $authenticator
            ->expects($this->once())
            ->method('authenticateFrontendGuest')
            ->with(true)
            ->willReturnCallback(
                static function () use ($session): bool {
                    $session->set(FrontendPreviewAuthenticator::SESSION_NAME, ['showUnpublished' => true]);

                    return true;
                },
            )
        ;

        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(static fn (Request $request): bool => 'https://example.org/example.html' === $request->getUri()
                    && true === $request->attributes->get('_preview')
                    && true === $request->getSession()->get(FrontendPreviewAuthenticator::SESSION_NAME)['showUnpublished']),
                HttpKernelInterface::SUB_REQUEST,
            )
            ->willReturn(new Response('<html><head><title>Example &amp; preview</title></head><body>Unpublished</body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']))
        ;

        $result = new FrontendTools($kernel, $stack, $dispatcher, $authenticator, $this->createSecurityStub())->inspect(42);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Example & preview', $result['title']);
        $this->assertStringContainsString('Unpublished', $result['html']);
        $this->assertFalse($result['truncated']);
        $this->assertSame(['showUnpublished' => false], $session->get(FrontendPreviewAuthenticator::SESSION_NAME));
    }

    public function testRejectsUnknownPages(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIs('Could not create a frontend preview URL for page ID 42.');

        $session = new Session(new MockArraySessionStorage());
        $stack = new RequestStack();
        $request = Request::create('https://example.org/contao/mcp');
        $request->setSession($session);
        $stack->push($request);

        new FrontendTools(
            $this->createStub(HttpKernelInterface::class),
            $stack,
            new EventDispatcher(),
            $this->createStub(FrontendPreviewAuthenticator::class),
            $this->createSecurityStub(),
        )->inspect(42);
    }

    private function createSecurityStub(): Security
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        return $security;
    }
}
