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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TokenCheckerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $tokenChecker = new TokenChecker(
            $this->createMock(SessionInterface::class),
            $this->createMock(AuthenticationTrustResolverInterface::class)
        );

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\Token\TokenChecker', $tokenChecker);
    }

    /**
     * @dataProvider getFrontendUserData
     */
    public function testChecksIfThereIsAFrontendUser(string $class, bool $expect): void
    {
        $user = $this->createMock($class);
        $token = new UsernamePasswordToken($user, 'password', 'provider', ['ROLE_USER']);
        $tokenChecker = $this->mockTokenChecker($token);

        $this->assertSame($expect, $tokenChecker->hasFrontendUser());
    }

    /**
     * @return (string|bool)[][]
     */
    public function getFrontendUserData(): array
    {
        return [
            [FrontendUser::class, true],
            [BackendUser::class, false],
        ];
    }

    /**
     * @dataProvider getBackendUserData
     */
    public function testChecksIfThereIsABackendUser(string $class, bool $expect): void
    {
        $user = $this->createMock($class);
        $token = new UsernamePasswordToken($user, 'password', 'provider', ['ROLE_USER']);
        $tokenChecker = $this->mockTokenChecker($token);

        $this->assertSame($expect, $tokenChecker->hasBackendUser());
    }

    /**
     * @return (string|bool)[][]
     */
    public function getBackendUserData(): array
    {
        return [
            [BackendUser::class, true],
            [FrontendUser::class, false],
        ];
    }

    public function testReturnsTheFrontendUsername(): void
    {
        $user = $this->createMock(FrontendUser::class);
        $user
            ->method('getUsername')
            ->willReturn('foobar')
        ;

        $token = new UsernamePasswordToken($user, 'password', 'provider', ['ROLE_USER']);
        $tokenChecker = $this->mockTokenChecker($token);

        $this->assertSame('foobar', $tokenChecker->getFrontendUsername());
    }

    public function testReturnsTheBackendUsername(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->method('getUsername')
            ->willReturn('foobar')
        ;

        $token = new UsernamePasswordToken($user, 'password', 'provider', ['ROLE_USER']);
        $tokenChecker = $this->mockTokenChecker($token);

        $this->assertSame('foobar', $tokenChecker->getBackendUsername());
    }

    /**
     * @dataProvider getPreviewModeData
     */
    public function testChecksIfThePreviewModeIsActive(TokenInterface $token, bool $expect): void
    {
        $tokenChecker = $this->mockTokenChecker($token);

        $this->assertSame($expect, $tokenChecker->isPreviewMode());
    }

    /**
     * @return (FrontendPreviewToken|UsernamePasswordToken|bool)[][]
     */
    public function getPreviewModeData(): array
    {
        return [
            [new FrontendPreviewToken(null, true), true],
            [new FrontendPreviewToken(null, false), false],
            [new UsernamePasswordToken('user', 'password', 'provider'), false],
        ];
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

        $trustResolver = $this->createMock(AuthenticationTrustResolverInterface::class);
        $tokenChecker = new TokenChecker($session, $trustResolver);

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

        $trustResolver = $this->createMock(AuthenticationTrustResolverInterface::class);
        $tokenChecker = new TokenChecker($session, $trustResolver);

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

        $trustResolver = new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class);
        $tokenChecker = new TokenChecker($session, $trustResolver);

        $this->assertNull($tokenChecker->getFrontendUsername());
    }

    public function testDoesNotReturnATokenIfTheTokenIsNotAuthenticated(): void
    {
        $token = new UsernamePasswordToken('user', 'password', 'provider');
        $tokenChecker = $this->mockTokenChecker($token);

        $this->assertNull($tokenChecker->getBackendUsername());
    }

    public function testDoesNotReturnATokenIfTheTokenIsAnonymous(): void
    {
        $token = new AnonymousToken('secret', 'anon.');
        $tokenChecker = $this->mockTokenChecker($token);

        $this->assertFalse($tokenChecker->isPreviewMode());
    }

    private function mockTokenChecker(TokenInterface $token): TokenChecker
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

        $trustResolver = new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class);

        return new TokenChecker($session, $trustResolver);
    }
}
