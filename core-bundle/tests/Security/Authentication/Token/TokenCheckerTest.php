<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication\Token;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;

class TokenCheckerTest extends TestCase
{
    /**
     * @dataProvider getUserInTokenStorageData
     */
    public function testChecksForUserInTokenStorageIfFirewallContextMatches(string $class, string $firewallContext, array $roles): void
    {
        $user = $this->mockUser($class);
        $token = new UsernamePasswordToken($user, 'provider', $roles);

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('isStarted')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext($firewallContext),
            $tokenStorage,
            $session,
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        $hasRoles = \count($roles);

        if (FrontendUser::class === $class) {
            if ($hasRoles) {
                $this->assertTrue($tokenChecker->hasFrontendUser());
            } else {
                $this->assertFalse($tokenChecker->hasFrontendUser());
            }
        } elseif ($hasRoles) {
            $this->assertTrue($tokenChecker->hasBackendUser());
        } else {
            $this->assertFalse($tokenChecker->hasBackendUser());
        }
    }

    public function getUserInTokenStorageData(): \Generator
    {
        yield [FrontendUser::class, 'contao_frontend', []];
        yield [FrontendUser::class, 'contao_frontend', ['ROLE_MEMBER']];
        yield [BackendUser::class, 'contao_backend', []];
        yield [BackendUser::class, 'contao_backend', ['ROLE_USER']];
    }

    /**
     * @dataProvider getUserInSessionData
     */
    public function testChecksForUserInSessionIfFirewallContextDoesNotMatch(string $class, string $firewallContext, array $roles): void
    {
        $user = $this->mockUser($class);
        $token = new UsernamePasswordToken($user, 'provider', $roles);

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('isStarted')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext($firewallContext),
            $tokenStorage,
            $session,
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        if (FrontendUser::class === $class) {
            $this->assertTrue($tokenChecker->hasFrontendUser());
        } else {
            $this->assertTrue($tokenChecker->hasBackendUser());
        }
    }

    public function getUserInSessionData(): \Generator
    {
        yield [BackendUser::class, 'contao_backend', ['ROLE_USER']];
        yield [FrontendUser::class, 'contao_frontend', ['ROLE_MEMBER']];
    }

    public function testReturnsTheFrontendUsername(): void
    {
        $user = $this->mockUser(FrontendUser::class);
        $token = new UsernamePasswordToken($user, 'provider', ['ROLE_MEMBER']);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(FrontendUser::class),
            $this->mockSessionWithToken($token),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        $this->assertSame('foobar', $tokenChecker->getFrontendUsername());
    }

    public function testReturnsTheBackendUsername(): void
    {
        $user = $this->mockUser(BackendUser::class);
        $token = new UsernamePasswordToken($user, 'provider', ['ROLE_USER']);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_frontend'),
            $this->mockTokenStorage(BackendUser::class),
            $this->mockSessionWithToken($token),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        $this->assertSame('foobar', $tokenChecker->getBackendUsername());
    }

    /**
     * @dataProvider getPreviewModeData
     */
    public function testChecksIfThePreviewModeIsActive(bool $isPreview, bool $expect): void
    {
        $request = new Request();

        $session = $this->mockSessionWithPreview($isPreview);

        if ($isPreview) {
            $request->attributes->set('_preview', true);
            $request->cookies->set($session->getName(), 'foo');
        }

        $request->setSession($session);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            $session,
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        $this->assertSame($expect, $tokenChecker->isPreviewMode());
    }

    public function getPreviewModeData(): \Generator
    {
        yield [false, false];
        yield [true, true];
    }

    public function testDoesNotReturnATokenIfTheSessionIsNotStarted(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(false)
        ;

        $session
            ->expects($this->never())
            ->method('has')
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            $session,
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        $this->assertFalse($tokenChecker->hasFrontendUser());
    }

    public function testDoesNotReturnATokenIfTheSessionKeyIsNotSet(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('has')
            ->willReturn(false)
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_frontend'),
            $this->mockTokenStorage(FrontendUser::class),
            $session,
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        $this->assertFalse($tokenChecker->hasBackendUser());
    }

    public function testDoesNotReturnATokenIfTheSerializedObjectIsNotAToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('get')
            ->willReturn(serialize(new \stdClass()))
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            $session,
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        $this->assertNull($tokenChecker->getFrontendUsername());
    }

    public function testDoesNotReturnATokenIfTheTokenIsNotAuthenticated(): void
    {
        $token = new UsernamePasswordToken($this->createMock(FrontendUser::class), 'provider');

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_frontend'),
            $this->mockTokenStorage(FrontendUser::class),
            $this->mockSessionWithToken($token),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        $this->assertNull($tokenChecker->getBackendUsername());
    }

    public function testDoesNotReturnATokenIfTheTokenIsAnonymous(): void
    {
        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            $this->mockSession(),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter()
        );

        $this->assertNull($tokenChecker->getFrontendUsername());
    }

    /**
     * @param class-string<User> $class
     *
     * @return User&MockObject
     */
    private function mockUser(string $class): User
    {
        $user = $this->createPartialMock($class, []);
        $user->id = 1;
        $user->username = 'foobar';

        return $user;
    }

    /**
     * @return RequestStack&MockObject
     */
    private function mockRequestStack(Request $request = null): RequestStack
    {
        $request ??= $this->createMock(Request::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getMainRequest')
            ->willReturn($request)
        ;

        return $requestStack;
    }

    /**
     * @return FirewallMap&MockObject
     */
    private function mockFirewallMapWithConfigContext(string $context): FirewallMap
    {
        $config = new FirewallConfig('', '', null, true, false, null, $context);

        $map = $this->createMock(FirewallMap::class);
        $map
            ->method('getFirewallConfig')
            ->willReturn($config)
        ;

        return $map;
    }

    /**
     * @return SessionInterface&MockObject
     */
    private function mockSessionWithToken(TokenInterface $token): SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('get')
            ->willReturn(serialize($token))
        ;

        return $session;
    }

    /**
     * @return SessionInterface&MockObject
     */
    private function mockSessionWithPreview(bool $isPreview): SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($isPreview ? $this->once() : $this->never())
            ->method('has')
            ->with(FrontendPreviewAuthenticator::SESSION_NAME)
            ->willReturn(true)
        ;

        $session
            ->expects($isPreview ? $this->exactly(2) : $this->never())
            ->method('getName')
            ->willReturn('foo')
        ;

        $session
            ->expects($isPreview ? $this->once() : $this->never())
            ->method('get')
            ->with(FrontendPreviewAuthenticator::SESSION_NAME)
            ->willReturn(['showUnpublished' => $isPreview])
        ;

        return $session;
    }

    private function getRoleVoter(): RoleVoter
    {
        return new RoleVoter('ROLE_');
    }
}
