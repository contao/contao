<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Security;

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\Security\LogoutListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;

class LogoutListenerTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_USERNAME']);

        parent::tearDown();
    }

    public function testReturnsIfResponseIsAlreadySet(): void
    {
        $response = new Response();

        $event = new LogoutEvent(new Request(), null);
        $event->setResponse($response);

        $listener = $this->mockLogoutListener();
        $listener($event);

        $this->assertSame($response, $event->getResponse());
    }

    public function testRedirectsToAGivenUrl(): void
    {
        $request = new Request();
        $request->query->set('redirect', 'http://localhost/home');

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $event = new LogoutEvent($request, null);

        $listener = $this->mockLogoutListener($httpUtils, $scopeMatcher);
        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/home', $response->getTargetUrl());
    }

    public function testRedirectsToTargetPath(): void
    {
        $request = new Request();
        $request->request->set('_target_path', 'http://localhost/home');

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $event = new LogoutEvent($request, null);

        $listener = $this->mockLogoutListener($httpUtils, $scopeMatcher);
        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/home', $response->getTargetUrl());
    }

    public function testRedirectsToTheRefererUrl(): void
    {
        $request = new Request();
        $request->headers->set('Referer', 'http://localhost/home');

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $event = new LogoutEvent($request, null);

        $listener = $this->mockLogoutListener($httpUtils, $scopeMatcher);
        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/home', $response->getTargetUrl());
    }

    public function testRedirectsToTheLoginScreenInTheBackend(): void
    {
        $request = new Request();

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'contao_backend_login')
            ->willReturn(new RedirectResponse('contao_backend_login'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $event = new LogoutEvent($request, null);

        $listener = $this->mockLogoutListener($httpUtils, $scopeMatcher);

        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('contao_backend_login', $response->getTargetUrl());
    }

    public function testAddsTheLogEntry(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('User "foobar" has logged out')
        ;

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $response = new Response();

        $event = new LogoutEvent(new Request(), null);
        $event->setResponse($response);

        $listener = $this->mockLogoutListener(null, null, $security, $logger);
        $listener($event);
    }

    public function testDoesNotAddALogEntryIfTheUserIsNotSupported(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('info')
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $response = new Response();

        $event = new LogoutEvent(new Request(), null);
        $event->setResponse($response);

        $listener = $this->mockLogoutListener(null, null, $security, $logger);
        $listener($event);
    }

    public function testRemovesTargetPathFromSessionWithUsernamePasswordToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('_security.contao_frontend.target_path')
        ;

        $request = new Request();
        $request->request->set('_target_path', 'http://localhost/home');
        $request->setSession($session);

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->expects($this->once())
            ->method('getFirewallName')
            ->willReturn('contao_frontend')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $response = new Response();

        $event = new LogoutEvent($request, null);
        $event->setResponse($response);

        $listener = $this->mockLogoutListener($httpUtils, $scopeMatcher, $security);
        $listener($event);
    }

    public function testRemovesTargetPathFromSessionWithTwoFactorToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('_security.contao_frontend.target_path')
        ;

        $request = new Request();
        $request->request->set('_target_path', 'http://localhost/home');
        $request->setSession($session);

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getFirewallName')
            ->willReturn('contao_frontend')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $response = new Response();

        $event = new LogoutEvent($request, null);
        $event->setResponse($response);

        $listener = $this->mockLogoutListener($httpUtils, $scopeMatcher, $security);
        $listener($event);
    }

    private function mockLogoutListener(HttpUtils|null $httpUtils = null, ScopeMatcher|null $scopeMatcher = null, Security|null $security = null, LoggerInterface|null $logger = null): LogoutListener
    {
        if (null === $httpUtils) {
            $httpUtils = $this->createMock(HttpUtils::class);
        }

        if (null === $scopeMatcher) {
            $scopeMatcher = $this->createMock(ScopeMatcher::class);
        }

        if (null === $security) {
            $security = $this->createMock(Security::class);
            $security
                ->expects($this->once())
                ->method('getToken')
            ;
        }

        return new LogoutListener($httpUtils, $scopeMatcher, $security, $logger);
    }
}
