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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendUser;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorFrontendListenerTest extends TestCase
{
    public function testReturnsIfRequestIsNotAFrontendRequest(): void
    {
        $request = $this->mockRequest();

        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(false, $request);
        $tokenStorage = $this->mockTokenStorage();

        $listener = new TwoFactorFrontendListener($scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsIfTokenIsNotATwoFactorToken(): void
    {
        $request = $this->mockRequest();

        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $request);
        $tokenStorage = $this->mockTokenStorage();

        $listener = new TwoFactorFrontendListener($scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsIfRequestHasNoPageModel(): void
    {
        $request = $this->mockRequest();

        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class);

        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $request);
        $tokenStorage = $this->mockTokenStorage($token);

        $listener = new TwoFactorFrontendListener($scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsIfUserIsNotAFrontendUser(): void
    {
        $request = $this->mockRequest(true);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class);

        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $request);
        $tokenStorage = $this->mockTokenStorage($token);

        $listener = new TwoFactorFrontendListener($scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    private function mockToken(string $class, bool $withFrontendUser = false)
    {
        $token = $this->createMock($class);

        if ($withFrontendUser) {
            $token
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($this->createMock(FrontendUser::class))
            ;
        }

        return $token;
    }

    private function mockRequest(bool $withPageModel = false): Request
    {
        $request = new Request();
        $request->attributes->set('pageModel', null);

        if ($withPageModel) {
            $request->attributes->set('pageModel', $this->createMock(PageModel::class));
        }

        return $request;
    }

    private function mockTokenStorage(TokenInterface $token = null): TokenStorage
    {
        $tokenStorage = $this->createMock(TokenStorage::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
    }

    private function mockScopeMatcher(bool $hasFrontendUser, Request $request): ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn($hasFrontendUser)
        ;

        return $scopeMatcher;
    }

    private function mockGetResponseEvent(Request $request = null): GetResponseEvent
    {
        if (null === $request) {
            $request = new Request();
        }

        $event = $this->createMock(GetResponseEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        return $event;
    }
}
