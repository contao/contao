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
use Contao\CoreBundle\Security\Authentication\Token\FrontendPreviewToken;
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
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TokenCheckerTest extends TestCase
{
    /**
     * @var AuthenticationTrustResolver
     */
    private $trustResolver;

    protected function setUp(): void
    {
        $this->trustResolver = new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class);
    }

    /**
     * @dataProvider getUserInTokenStorageData
     */
    public function testChecksForUserInTokenStorageIfFirewallContextMatches(string $class, string $firewallContext): void
    {
        $user = $this->mockUser($class);
        $token = new UsernamePasswordToken($user, 'password', 'provider', ['ROLE_USER']);

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
            $this->trustResolver
        );

        if (FrontendUser::class === $class) {
            $this->assertTrue($tokenChecker->hasFrontendUser());
        } else {
            $this->assertTrue($tokenChecker->hasBackendUser());
        }
    }

    public function getUserInTokenStorageData(): \Generator
    {
        yield [FrontendUser::class, 'contao_frontend'];
        yield [BackendUser::class, 'contao_backend'];
    }

    /**
     * @dataProvider getUserInSessionData
     */
    public function testChecksForUserInSessionIfFirewallContextDoesNotMatch(string $class, string $firewallContext): void
    {
        $user = $this->mockUser($class);
        $token = new UsernamePasswordToken($user, 'password', 'provider', ['ROLE_USER']);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext($firewallContext),
            $this->mockTokenStorage($class),
            $this->mockSessionWithToken($token),
            $this->trustResolver
        );

        if (FrontendUser::class === $class) {
            $this->assertTrue($tokenChecker->hasFrontendUser());
        } else {
            $this->assertTrue($tokenChecker->hasBackendUser());
        }
    }

    public function getUserInSessionData(): \Generator
    {
        yield [FrontendUser::class, 'contao_backend'];
        yield [BackendUser::class, 'contao_frontend'];
    }

    public function testReturnsTheFrontendUsername(): void
    {
        $user = $this->mockUser(FrontendUser::class);
        $token = new UsernamePasswordToken($user, 'password', 'provider', ['ROLE_USER']);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(FrontendUser::class),
            $this->mockSessionWithToken($token),
            $this->trustResolver
        );

        $this->assertSame('foobar', $tokenChecker->getFrontendUsername());
    }

    public function testReturnsTheBackendUsername(): void
    {
        $user = $this->mockUser(BackendUser::class);
        $token = new UsernamePasswordToken($user, 'password', 'provider', ['ROLE_USER']);

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_frontend'),
            $this->mockTokenStorage(BackendUser::class),
            $this->mockSessionWithToken($token),
            $this->trustResolver
        );

        $this->assertSame('foobar', $tokenChecker->getBackendUsername());
    }

    /**
     * @dataProvider getPreviewModeData
     */
    public function testChecksIfThePreviewModeIsActive(TokenInterface $token, string $script, bool $expect): void
    {
        $request = new Request();
        $request->server->set('SCRIPT_NAME', $script);

        if ('' !== $script) {
            $session = $this->mockSessionWithToken($token);
        } else {
            $session = $this->createMock(SessionInterface::class);
            $session
                ->expects($this->never())
                ->method('isStarted')
            ;
        }

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack($request),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            $session,
            $this->trustResolver,
            '/preview.php'
        );

        $this->assertSame($expect, $tokenChecker->isPreviewMode());
    }

    public function getPreviewModeData(): \Generator
    {
        yield [new FrontendPreviewToken(null, true), '', false];
        yield [new FrontendPreviewToken(null, true), '/preview.php', true];
        yield [new FrontendPreviewToken(null, false), '/preview.php', false];
        yield [new UsernamePasswordToken('user', 'password', 'provider'), '/preview.php', false];
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
            $this->trustResolver
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
            $this->trustResolver
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
            $this->trustResolver
        );

        $this->assertNull($tokenChecker->getFrontendUsername());
    }

    public function testDoesNotReturnATokenIfTheTokenIsNotAuthenticated(): void
    {
        $token = new UsernamePasswordToken('user', 'password', 'provider');

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_frontend'),
            $this->mockTokenStorage(FrontendUser::class),
            $this->mockSessionWithToken($token),
            $this->trustResolver
        );

        $this->assertNull($tokenChecker->getBackendUsername());
    }

    public function testDoesNotReturnATokenIfTheTokenIsAnonymous(): void
    {
        $token = new AnonymousToken('secret', 'anon.');

        $tokenChecker = new TokenChecker(
            $this->mockRequestStack(),
            $this->mockFirewallMapWithConfigContext('contao_backend'),
            $this->mockTokenStorage(BackendUser::class),
            $this->mockSessionWithToken($token),
            $this->trustResolver
        );

        $this->assertNull($tokenChecker->getFrontendUsername());
    }

    private function mockUser(string $class): User
    {
        /** @var User&MockObject $user */
        $user = $this->createPartialMock($class, []);
        $user->id = 1;
        $user->username = 'foobar';

        return $user;
    }

    /**
     * @param Request&MockObject $request
     *
     * @return RequestStack&MockObject
     */
    private function mockRequestStack(Request $request = null): RequestStack
    {
        if (null === $request) {
            $request = $this->createMock(Request::class);
        }

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getMasterRequest')
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
}
