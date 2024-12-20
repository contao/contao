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
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\PageFinder;
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
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
            $this->mockPageFinder(),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(TokenStorage::class),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testReturnsIfTheTokenIsNotATwoFactorToken(): void
    {
        $event = $this->getRequestEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder(),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockTokenStorageWithToken(),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testReturnsIfTheTokenIsNotSupported(): void
    {
        $token = $this->createMock(NullToken::class);
        $event = $this->getRequestEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder(),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testDoesNotEnforcesTwoFactorIfTheUserIsNotAFrontendUser(): void
    {
        $rootPage = $this->mockClassWithProperties(PageModel::class, ['enforceTwoFactor' => true]);
        $token = $this->mockToken(UsernamePasswordToken::class);
        $event = $this->getRequestEvent($this->getRequest(true));

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder($rootPage),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockTokenStorageWithToken($token),
            [$token::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testThrowsAPageNotFoundExceptionIfThereIsNoTwoFactorPage(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $rootPage = $this->mockClassWithProperties(PageModel::class);
        $rootPage->enforceTwoFactor = true;
        $rootPage->twoFactorJumpTo = 0;

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn(null)
        ;

        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true));

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder($rootPage),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockTokenStorageWithToken($token),
            [$token::class],
        );

        $this->expectException(ForwardPageNotFoundException::class);
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
            $this->mockPageFinder($pageModel),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
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

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn($twoFactorPageModel)
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($twoFactorPageModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/two_factor')
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder($pageModel),
            $urlGenerator,
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
            $this->mockPageFinder($pageModel),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class, $token::class],
        );

        $listener($event);

        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
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

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);

        $request = $this->getRequest(true, $pageModel);
        $request->setSession($session);

        $event = $this->getRequestEvent($request, $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder($pageModel, $page401),
            $this->createMock(ContentUrlGenerator::class),
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

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder($pageModel, $page401),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testThrowsAnInsufficientAuthenticationException(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = false;

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = false;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getRequestEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder($pageModel),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $this->expectException(InsufficientAuthenticationException::class);

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

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);

        $request = $this->getRequest(true, $pageModel);
        $request->setSession($session);

        $event = $this->getRequestEvent($request, $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder($pageModel),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class],
        );

        $listener($event);

        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
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

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);

        $request = $this->getRequest(true, $pageModel);
        $request->setSession($session);

        $event = $this->getRequestEvent($request, $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcherWithEvent(true, $event),
            $this->mockPageFinder($pageModel),
            $this->createMock(ContentUrlGenerator::class),
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
        $request->attributes->set('_scope', 'frontend');
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

    private function mockPageFinder(PageModel|null $rootPage = null, PageModel|null $errorPage = null): PageFinder&MockObject
    {
        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($rootPage ? $this->once() : $this->any())
            ->method('findRootPageForRequest')
            ->with($this->isInstanceOf(Request::class))
            ->willReturn($rootPage)
        ;

        $pageFinder
            ->expects($errorPage ? $this->once() : $this->any())
            ->method('findFirstPageOfTypeForRequest')
            ->with($this->isInstanceOf(Request::class), 'error_401')
            ->willReturn($errorPage)
        ;

        return $pageFinder;
    }
}
