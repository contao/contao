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

use Contao\CoreBundle\EventListener\Security\TwoFactorFrontendListener;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TwoFactorFrontendListenerTest extends TestCase
{
    public function testReturnsIfTheRequestIsNotAFrontendRequest(): void
    {
        $event = $this->getRequestEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(false, $event),
            $this->createMock(TokenStorage::class),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testReturnsIfTheTokenIsNotATwoFactorToken(): void
    {
        $event = $this->getRequestEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken(),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testReturnsIfTheTokenIsNotSupported(): void
    {
        $token = $this->createMock(NullToken::class);
        $event = $this->getRequestEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testReturnsIfTheRequestHasNoPageModel(): void
    {
        $token = $this->mockToken(TwoFactorToken::class);
        $event = $this->getRequestEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        $token = $this->mockToken(TwoFactorToken::class);
        $event = $this->getRequestEvent($this->getRequest(true));

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testThrowsAPageNotFoundExceptionIfThereIsNoTwoFactorPage(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->enforceTwoFactor = true;
        $pageModel->twoFactorJumpTo = 0;

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn(null)
        ;

        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true, $pageModel));

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionMessage('No two-factor authentication page found');

        $listener($event);
    }

    public function testReturnsIfTwoFactorAuthenticationIsEnforcedAndThePageIsTheTwoFactorPage(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = true;
        $pageModel->twoFactorJumpTo = 1;

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn($pageModel)
        ;

        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true, $pageModel));

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testRedirectsToTheTwoFactorPageIfTwoFactorAuthenticationIsEnforced(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = true;
        $pageModel->twoFactorJumpTo = 2;

        $twoFactorPageModel = $this->mockClassWithProperties(PageModel::class);
        $twoFactorPageModel->id = 2;

        $twoFactorPageModel
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('http://localhost/two_factor')
        ;

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn($twoFactorPageModel)
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/two_factor', $response->getTargetUrl());
    }

    public function testReturnsIfTheUserIsAlreadyAuthenticated(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = true;

        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->never())
            ->method('find401ByPid')
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(UsernamePasswordToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class, $token::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testRedirectsToTheTargetPathIfThe401PageHasNoRedirect(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = false;
        $pageModel->twoFactorJumpTo = 1;

        $page401 = $this->mockClassWithProperties(PageModel::class);
        $page401->autoforward = true;

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->willReturn('http://localhost/foobar')
        ;

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn($page401)
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);

        $request = $this->getRequest(true, $pageModel);
        $request->setSession($session);

        $event = $this->getRequestEvent($request, $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/foobar', $response->getTargetUrl());
    }

    public function testReturnsIfTheCurrentPageIsThe401AutoforwardTarget(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = false;
        $pageModel->twoFactorJumpTo = 1;

        $page401 = $this->mockClassWithProperties(PageModel::class);
        $page401->autoforward = true;
        $page401->jumpTo = 1;

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn($page401)
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testThrowsAnUnauthorizedHttpException(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = false;

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn(null)
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $this->expectException(UnauthorizedHttpException::class);

        $listener($event);
    }

    public function testReturnsIfTheCurrentPageIsTheTargetPath(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = false;

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->willReturn('http://:')
        ;

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn(null)
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);

        $request = $this->getRequest(true, $pageModel);
        $request->setSession($session);

        $event = $this->getRequestEvent($request, $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testRedirectsToTheTargetPath(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = false;

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->willReturn('http://localhost/foobar')
        ;

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn(null)
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);

        $request = $this->getRequest(true, $pageModel);
        $request->setSession($session);

        $event = $this->getRequestEvent($request, $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/foobar', $response->getTargetUrl());
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T&MockObject
     */
    private function mockToken(string $class, bool $withFrontendUser = false, FrontendUser|null $user = null): MockObject
    {
        $token = $this->createMock($class);
        $user ??= $this->createMock(FrontendUser::class);

        if ($withFrontendUser) {
            $token
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($user)
            ;
        }

        $token
            ->expects($this->atMost(2))
            ->method('getFirewallName')
            ->willReturn('contao_frontend')
        ;

        return $token;
    }

    private function getRequest(bool $withPageModel = false, PageModel|null $pageModel = null): Request
    {
        $request = new Request();
        $request->attributes->set('pageModel', null);

        $pageModel ??= $this->createMock(PageModel::class);

        if ($withPageModel) {
            $request->attributes->set('pageModel', $pageModel);
        }

        $request->setSession($this->createMock(SessionInterface::class));

        return $request;
    }

    private function mockTokenStorageWithToken(TokenInterface|null $token = null): TokenStorageInterface&MockObject
    {
        $tokenStorage = $this->createMock(TokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
    }

    private function mockScopeMatcherWithEvent(bool $hasFrontendUser, RequestEvent $event): ScopeMatcher&MockObject
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendMainRequest')
            ->with($event)
            ->willReturn($hasFrontendUser)
        ;

        return $scopeMatcher;
    }

    private function getRequestEvent(Request|null $request = null, Response|null $response = null): RequestEvent
    {
        $kernel = $this->createMock(Kernel::class);
        $event = new RequestEvent($kernel, $request ?? new Request(), HttpKernelInterface::MAIN_REQUEST);

        if ($response) {
            $event->setResponse($response);
        }

        return $event;
    }
}
