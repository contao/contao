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
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
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

        $request = new Request();
        $request->setSession($session);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext($firewallContext),
            $tokenStorage,
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
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

        $request = new Request();
        $request->setSession($session);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext($firewallContext),
            $tokenStorage,
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
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
        $session = $this->mockSessionWithToken($token);

        $request = new Request();
        $request->setSession($session);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(FrontendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
        );

        $this->assertSame('foobar', $tokenChecker->getFrontendUsername());
    }

    public function testReturnsTheBackendUsername(): void
    {
        $user = $this->mockUser(BackendUser::class);
        $token = new UsernamePasswordToken($user, 'provider', ['ROLE_USER']);
        $session = $this->mockSessionWithToken($token);

        $request = new Request();
        $request->setSession($session);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_frontend'),
            $this->mockTokenStorage(BackendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
        );

        $this->assertSame('foobar', $tokenChecker->getBackendUsername());
    }

    /**
     * @dataProvider getPreviewAllowedData
     */
    public function testChecksIfAccessingThePreviewIsAllowed(array $token, array|null $previewLinkRow, string|null $url, bool $expect): void
    {
        $request = new Request();

        if ($url) {
            $request = Request::create($url);
        }

        $session = $this->createMock(Session::class);
        $session
            ->method('get')
            ->with(FrontendPreviewAuthenticator::SESSION_NAME)
            ->willReturn($token)
        ;

        $request->setSession($session);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn($previewLinkRow)
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(FrontendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $connection,
        );

        $this->assertSame($expect, $tokenChecker->canAccessPreview());
    }

    public function getPreviewAllowedData(): \Generator
    {
        yield 'Valid preview' => [
            ['showUnpublished' => true, 'previewLinkId' => 1],
            [
                'url' => 'https://localhost/',
                'showUnpublished' => true,
                'restrictToUrl' => false,
            ],
            null,
            true,
        ];

        yield 'Valid preview restricted to URL' => [
            ['showUnpublished' => true, 'previewLinkId' => 1],
            [
                'url' => 'https://localhost/page1?foo=bar',
                'showUnpublished' => true,
                'restrictToUrl' => true,
            ],
            'https://localhost/page1?bar=baz',
            true,
        ];

        yield 'Valid preview restricted to URL (formatted differently)' => [
            ['showUnpublished' => true, 'previewLinkId' => 1],
            [
                'url' => 'https://example.com:443/page1',
                'showUnpublished' => true,
                'restrictToUrl' => true,
            ],
            'https://example.com/page1?foo=bar',
            true,
        ];

        yield 'Invalid preview restricted to different URL' => [
            ['showUnpublished' => true, 'previewLinkId' => 1],
            [
                'url' => 'https://localhost/page2',
                'showUnpublished' => true,
                'restrictToUrl' => true,
            ],
            'https://localhost/page1',
            false,
        ];

        yield 'Missing preview link ID' => [
            ['showUnpublished' => true, 'previewLinkId' => null],
            [
                'url' => 'https://localhost/',
                'showUnpublished' => true,
                'restrictToUrl' => false,
            ],
            null,
            false,
        ];

        yield 'Missing DB row' => [['showUnpublished' => true, 'previewLinkId' => 1], null, null, false];

        yield 'showUnpublished mismatch' => [
            ['showUnpublished' => true, 'previewLinkId' => 1],
            [
                'url' => 'https://localhost/',
                'showUnpublished' => false,
                'restrictToUrl' => false,
            ],
            null,
            false,
        ];
    }

    public function testAccessingThePreviewIsAllowedForBackendUser(): void
    {
        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($this->createMock(Request::class)),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
        );

        $this->assertTrue($tokenChecker->canAccessPreview());
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

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn([
                'url' => 'https://localhost/',
                'showUnpublished' => $isPreview,
                'restrictToUrl' => false,
            ])
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $connection,
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
            ->expects($this->once())
            ->method('getName')
            ->willReturn('foo')
        ;

        $session
            ->expects($this->never())
            ->method('has')
        ;

        $request = new Request();
        $request->setSession($session);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
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

        $request = new Request();
        $request->setSession($session);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_frontend'),
            $this->mockTokenStorage(FrontendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
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

        $request = new Request();
        $request->setSession($session);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
        );

        $this->assertNull($tokenChecker->getFrontendUsername());
    }

    public function testDoesNotReturnATokenIfTheTokenIsNotAuthenticated(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn(null)
        ;

        $session = $this->mockSessionWithToken($token);

        $request = new Request();
        $request->setSession($session);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_frontend'),
            $tokenStorage,
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
        );

        $this->assertNull($tokenChecker->getBackendUsername());
    }

    public function testDoesNotReturnATokenIfTheTokenIsAnonymous(): void
    {
        $session = $this->mockSession();

        $request = new Request();
        $request->setSession($session);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $this->createMock(Connection::class),
        );

        $this->assertNull($tokenChecker->getFrontendUsername());
    }

    /**
     * @dataProvider getFrontendGuestData
     */
    public function testIfAFrontendGuestIsAvailable(bool $expected, bool $hasFrontendGuest): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->with(FrontendPreviewAuthenticator::SESSION_NAME)
            ->willReturn($hasFrontendGuest ? ['showUnpublished' => false, 'previewLinkId' => 123] : null)
        ;

        $request = new Request();
        $request->setSession($session);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn([
                'url' => 'https://localhost/',
                'showUnpublished' => false,
                'restrictToUrl' => false,
            ])
        ;

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            new AuthenticationTrustResolver(),
            $this->getRoleVoter(),
            $connection,
        );

        $this->assertSame($expected, $tokenChecker->hasFrontendGuest());
    }

    public function getFrontendGuestData(): \Generator
    {
        yield [false, false];
        yield [true, true];
    }

    /**
     * @param class-string<User> $class
     *
     * @return User
     */
    private function mockUser(string $class): User
    {
        $user = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        $data = new \ReflectionProperty($user, 'arrData');
        $data->setValue($user, ['id' => 1, 'username' => 'foobar']);

        return $user;
    }

    private function mockRequestStack(Request $request): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

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
            ->expects($this->atLeastOnce())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->atLeastOnce())
            ->method('has')
            ->willReturn(true)
        ;

        $session
            ->expects($this->atLeastOnce())
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
