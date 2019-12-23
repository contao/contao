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

use Contao\CoreBundle\EventListener\TwoFactorFrontendListener;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TwoFactorFrontendListenerTest extends ContaoTestCase
{
    public function testReturnsIfTheRequestIsNotAFrontendRequest(): void
    {
        $event = $this->getResponseEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcher(false, $event),
            $this->createMock(TokenStorage::class),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testReturnsIfTheTokenIsNotATwoFactorToken(): void
    {
        $event = $this->getResponseEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken(),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testReturnsIfTheTokenIsNotSupported(): void
    {
        $token = $this->createMock(AnonymousToken::class);
        $event = $this->getResponseEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testReturnsIfTheRequestHasNoPageModel(): void
    {
        $token = $this->mockToken(TwoFactorToken::class);
        $event = $this->getResponseEvent($this->getRequest());

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        $token = $this->mockToken(TwoFactorToken::class);
        $event = $this->getResponseEvent($this->getRequest(true));

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework(),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testThrowsAPageNotFoundExceptionIfThereIsNoTwoFactorPage(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = '';

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->enforceTwoFactor = '1';
        $pageModel->twoFactorJumpTo = 0;

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn(null)
        ;

        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getResponseEvent($this->getRequest(true, $pageModel));

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionMessage('No two-factor authentication page found');

        $listener($event);
    }

    public function testReturnsIfTwoFactorAuthenticationIsEnforcedAndThePageIsTheTwoFactorPage(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = '';

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = '1';
        $pageModel->twoFactorJumpTo = 1;

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn($pageModel)
        ;

        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getResponseEvent($this->getRequest(true, $pageModel));

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testRedirectsToTheTwoFactorPageIfTwoFactorAuthenticationIsEnforced(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = '';

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = '1';
        $pageModel->twoFactorJumpTo = 2;

        /** @var PageModel&MockObject $twoFactorPageModel */
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
        $event = $this->getResponseEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/two_factor', $response->getTargetUrl());
    }

    public function testReturnsIfTheUserIsAlreadyAuthenticated(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = '1';

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->never())
            ->method('find401ByPid')
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(UsernamePasswordToken::class, true, $user);
        $event = $this->getResponseEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class, \get_class($token)]
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testRedirectsToTheTargetPathIfThe401PageHasNoRedirect(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = '';

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = '';
        $pageModel->twoFactorJumpTo = 1;

        /** @var PageModel&MockObject $page401 */
        $page401 = $this->mockClassWithProperties(PageModel::class);
        $page401->autoforward = '';

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

        $event = $this->getResponseEvent($request, $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/foobar', $response->getTargetUrl());
    }

    public function testReturnsIfTheCurrentPageIsThe401AutoforwardTarget(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = '';

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = '';
        $pageModel->twoFactorJumpTo = 1;

        /** @var PageModel&MockObject $page401 */
        $page401 = $this->mockClassWithProperties(PageModel::class);
        $page401->autoforward = '1';
        $page401->jumpTo = 1;

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn($page401)
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getResponseEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testThrowsAnUnauthorizedHttpException(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = '';

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = '';

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn(null)
        ;

        $response = new RedirectResponse('http://localhost/two_factor');
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $event = $this->getResponseEvent($this->getRequest(true, $pageModel), $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $this->expectException(UnauthorizedHttpException::class);

        $listener($event);
    }

    public function testReturnsIfTheCurrentPageIsTheTargetPath(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = '';

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = '';

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

        $event = $this->getResponseEvent($request, $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->hasResponse());
    }

    public function testRedirectsToTheTargetPath(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->useTwoFactor = '';

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 1;
        $pageModel->enforceTwoFactor = '';

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

        $event = $this->getResponseEvent($request, $response);

        $listener = new TwoFactorFrontendListener(
            $this->mockContaoFramework([PageModel::class => $adapter]),
            $this->mockScopeMatcher(true, $event),
            $this->mockTokenStorageWithToken($token),
            [UsernamePasswordToken::class]
        );

        $listener($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/foobar', $response->getTargetUrl());
    }

    /**
     * @return TokenInterface&MockObject
     */
    private function mockToken(string $class, bool $withFrontendUser = false, FrontendUser $user = null): TokenInterface
    {
        /** @var TokenInterface&MockObject $token */
        $token = $this->createMock($class);

        if (null === $user) {
            $user = $this->createMock(FrontendUser::class);
        }

        if ($withFrontendUser) {
            $token
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($user)
            ;
        }

        $token
            ->expects($this->atMost(2))
            ->method('getProviderKey')
            ->willReturn('contao_frontend')
        ;

        return $token;
    }

    private function getRequest(bool $withPageModel = false, PageModel $pageModel = null): Request
    {
        $request = new Request();
        $request->attributes->set('pageModel', null);

        if (null === $pageModel) {
            $pageModel = $this->createMock(PageModel::class);
        }

        if ($withPageModel) {
            $request->attributes->set('pageModel', $pageModel);
        }

        $request->setSession($this->createMock(SessionInterface::class));

        return $request;
    }

    /**
     * @return TokenStorageInterface&MockObject
     */
    private function mockTokenStorageWithToken(TokenInterface $token = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
    }

    /**
     * @return ScopeMatcher&MockObject
     */
    private function mockScopeMatcher(bool $hasFrontendUser, RequestEvent $event): ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendMasterRequest')
            ->with($event)
            ->willReturn($hasFrontendUser)
        ;

        return $scopeMatcher;
    }

    private function getResponseEvent(Request $request = null, Response $response = null): RequestEvent
    {
        $kernel = $this->createMock(Kernel::class);

        if (null === $request) {
            $request = new Request();
        }

        $event = new RequestEvent($kernel, $request, Kernel::MASTER_REQUEST);

        if (null !== $response) {
            $event->setResponse($response);
        }

        return $event;
    }
}
