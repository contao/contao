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
use Contao\McpBundle\Tool\FrontendTools;
use Contao\TestCase\ContaoTestCase;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class FrontendToolsTest extends ContaoTestCase
{
    public function testInspectsAPageInPreviewModeWithoutUsingTheStatelessParentSession(): void
    {
        $stack = new RequestStack();
        $request = Request::create('https://example.org/contao/mcp');
        $request->attributes->set('_stateless', true);
        $stack->push($request);

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            ContaoCoreEvents::PREVIEW_URL_CONVERT,
            static function (PreviewUrlConvertEvent $event): void {
                $event->setUrl('https://example.org/example.html');
            },
        );

        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(static fn (Request $request): bool => 'https://example.org/example.html' === $request->getUri()
                    && true === $request->attributes->get('_preview')
                    && true === $request->getSession()->get('_contao_frontend_preview')['showUnpublished']
                    && $request->cookies->has($request->getSession()->getName())
                    && $request->getSession()->has('_security_contao_backend')),
                HttpKernelInterface::SUB_REQUEST,
            )
            ->willReturn(new Response('<html><head><title>Example &amp; preview</title></head><body>Unpublished</body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']))
        ;

        $result = new FrontendTools($kernel, $stack, $dispatcher, $this->createSecurityStub())->inspect(42);

        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('Unpublished', $result['html']);
        $this->assertFalse($result['truncated']);
        $this->assertFalse($request->hasSession());
    }

    public function testRejectsUnknownPages(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIs('Could not create a frontend preview URL for page ID 42.');

        $stack = new RequestStack();
        $request = Request::create('https://example.org/contao/mcp');
        $stack->push($request);

        new FrontendTools(
            $this->createStub(HttpKernelInterface::class),
            $stack,
            new EventDispatcher(),
            $this->createSecurityStub(),
        )->inspect(42);
    }

    private function createSecurityStub(): Security
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('getToken')
            ->willReturn(new UsernamePasswordToken(new InMemoryUser('admin', null, ['ROLE_USER']), 'contao_mcp', ['ROLE_USER']))
        ;

        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        return $security;
    }
}
