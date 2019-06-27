<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\Authentication\Token\FrontendPreviewToken;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FrontendPreviewAuthenticatorTest extends TestCase
{
    public function testDoesNotAuthenticateIfTheSessionIsNotStarted(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(false)
        ;

        $authenticator = $this->getAuthenticator($session);

        $this->assertFalse($authenticator->authenticateFrontendUser('foobar', false));
    }

    public function testDoesNotAuthenticateIfTheTokenStorageIsEmpty(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(null)
        ;

        $authenticator = $this->getAuthenticator(null, $tokenStorage);

        $this->assertFalse($authenticator->authenticateFrontendUser('foobar', false));
    }

    public function testDoesNotAuthenticateIfTheTokenIsNotAuthenticated(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(false)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $authenticator = $this->getAuthenticator(null, $tokenStorage);

        $this->assertFalse($authenticator->authenticateFrontendUser('foobar', false));
    }

    public function testDoesNotAuthenticateIfTheTokenDoesNotContainABackendUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $authenticator = $this->getAuthenticator(null, $tokenStorage);

        $this->assertFalse($authenticator->authenticateFrontendUser('foobar', false));
    }

    /**
     * @dataProvider getAccessPermissions
     */
    public function testChecksTheBackendUsersAccessPermissions(bool $isAdmin, $amg, bool $isValid): void
    {
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->isAdmin = $isAdmin;
        $user->amg = $amg;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->expects($this->exactly((int) $isValid))
            ->method('loadUserByUsername')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $authenticator = $this->getAuthenticator(null, $tokenStorage, $userProvider);

        $this->assertFalse($authenticator->authenticateFrontendUser('foobar', false));
    }

    public function getAccessPermissions(): \Generator
    {
        yield [true, null, true];
        yield [false, null, false];
        yield [false, 'foobar', false];
        yield [false, [], false];
        yield [false, ['foobar'], true];
    }

    public function testDoesNotAuthenticateIfThereIsNotFrontendUser(): void
    {
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->isAdmin = true;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willThrowException(new UsernameNotFoundException())
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Could not find a front end user with the username "foobar"')
        ;

        $authenticator = $this->getAuthenticator(null, $tokenStorage, $userProvider, $logger);

        $this->assertFalse($authenticator->authenticateFrontendUser('foobar', false));
    }

    /**
     * @dataProvider getFrontendGroupAccessPermissions
     */
    public function testChecksTheBackendUsersFrontendGroupAccess(bool $isAdmin, $amg, $groups, bool $isValid): void
    {
        /** @var BackendUser&MockObject $backendUser */
        $backendUser = $this->mockClassWithProperties(BackendUser::class);
        $backendUser->isAdmin = $isAdmin;
        $backendUser->amg = $amg;

        /** @var FrontendUser&MockObject $frontendUser */
        $frontendUser = $this->mockClassWithProperties(FrontendUser::class);
        $frontendUser->groups = $groups;

        $frontendUser
            ->method('getRoles')
            ->willReturn([])
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($backendUser)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->method('loadUserByUsername')
            ->willReturn($frontendUser)
        ;

        $authenticator = $this->getAuthenticator(null, $tokenStorage, $userProvider);

        $this->assertSame($isValid, $authenticator->authenticateFrontendUser('foobar', false));
    }

    public function getFrontendGroupAccessPermissions(): \Generator
    {
        yield [false, null, null, false];
        yield [true, null, null, true];
        yield [false, [], [], false];
        yield [false, ['foo', 'bar'], [], false];
        yield [false, [], ['foo', 'bar'], false];
        yield [false, ['foo', 'bar'], ['foo', 'bar'], true];
        yield [false, ['foo', 'bar'], ['foo'], true];
    }

    public function testAuthenticatesAFrontendUserWithUnpublishedElementsHidden(): void
    {
        /** @var BackendUser&MockObject $backendUser */
        $backendUser = $this->mockClassWithProperties(BackendUser::class);
        $backendUser->isAdmin = true;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($backendUser)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $session = $this->mockSession();
        $session->start();

        /** @var FrontendUser&MockObject $frontendUser */
        $frontendUser = $this->createPartialMock(FrontendUser::class, ['getRoles']);
        $frontendUser
            ->method('getRoles')
            ->willReturn([])
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->method('loadUserByUsername')
            ->willReturn($frontendUser)
        ;

        $authenticator = $this->getAuthenticator($session, $tokenStorage, $userProvider);

        $this->assertTrue($authenticator->authenticateFrontendUser('foobar', false));
        $this->assertTrue($session->has('_security_contao_frontend'));

        $token = unserialize($session->get('_security_contao_frontend'), ['allowed_classes' => true]);

        $this->assertInstanceOf(FrontendPreviewToken::class, $token);
        $this->assertInstanceOf(FrontendUser::class, $token->getUser());
        $this->assertFalse($token->showUnpublished());
    }

    public function testAuthenticatesAFrontendUserWithUnpublishedElementsVisible(): void
    {
        /** @var BackendUser&MockObject $backendUser */
        $backendUser = $this->mockClassWithProperties(BackendUser::class);
        $backendUser->isAdmin = true;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($backendUser)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $session = $this->mockSession();
        $session->start();

        /** @var FrontendUser&MockObject $frontendUser */
        $frontendUser = $this->createPartialMock(FrontendUser::class, ['getRoles']);
        $frontendUser
            ->method('getRoles')
            ->willReturn([])
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->method('loadUserByUsername')
            ->willReturn($frontendUser)
        ;

        $authenticator = $this->getAuthenticator($session, $tokenStorage, $userProvider);

        $this->assertTrue($authenticator->authenticateFrontendUser('foobar', true));
        $this->assertTrue($session->has('_security_contao_frontend'));

        $token = unserialize($session->get('_security_contao_frontend'), ['allowed_classes' => true]);

        $this->assertInstanceOf(FrontendPreviewToken::class, $token);
        $this->assertInstanceOf(FrontendUser::class, $token->getUser());
        $this->assertTrue($token->showUnpublished());
    }

    public function testDoesNotAuthenticateGuestsIfThereIsNoBackendUser(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(null)
        ;

        $session = $this->mockSession();
        $session->start();

        $authenticator = $this->getAuthenticator($session, $tokenStorage);

        $this->assertFalse($authenticator->authenticateFrontendGuest(false));
    }

    public function testAuthenticatesGuestsWithUnpublishedElementsHidden(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(BackendUser::class))
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $session = $this->mockSession();
        $session->start();

        $authenticator = $this->getAuthenticator($session, $tokenStorage);

        $this->assertTrue($authenticator->authenticateFrontendGuest(false));
        $this->assertTrue($session->has('_security_contao_frontend'));

        $token = unserialize($session->get('_security_contao_frontend'), ['allowed_classes' => true]);

        $this->assertInstanceOf(FrontendPreviewToken::class, $token);
        $this->assertSame('anon.', $token->getUser());
        $this->assertFalse($token->showUnpublished());
    }

    public function testAuthenticatesGuestsWithUnpublishedElementsVisible(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(BackendUser::class))
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $session = $this->mockSession();
        $session->start();

        $authenticator = $this->getAuthenticator($session, $tokenStorage);

        $this->assertTrue($authenticator->authenticateFrontendGuest(true));
        $this->assertTrue($session->has('_security_contao_frontend'));

        $token = unserialize($session->get('_security_contao_frontend'), ['allowed_classes' => true]);

        $this->assertInstanceOf(FrontendPreviewToken::class, $token);
        $this->assertSame('anon.', $token->getUser());
        $this->assertTrue($token->showUnpublished());
    }

    public function testRemovesTheAuthenticationFromTheSession(): void
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
            ->with('_security_contao_frontend')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('remove')
            ->with('_security_contao_frontend')
        ;

        $authenticator = $this->getAuthenticator($session);

        $this->assertTrue($authenticator->removeFrontendAuthentication());
    }

    public function testDoesNotRemoveTheAuthenticationIfTheSessionIsNotStarted(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(false)
        ;

        $authenticator = $this->getAuthenticator($session);

        $this->assertFalse($authenticator->removeFrontendAuthentication());
    }

    public function testDoesNotRemoveTheAuthenticationIfTheSessionDoesNotContainAToken(): void
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
            ->with('_security_contao_frontend')
            ->willReturn(false)
        ;

        $authenticator = $this->getAuthenticator($session);

        $this->assertFalse($authenticator->removeFrontendAuthentication());
    }

    /**
     * @param SessionInterface&MockObject      $session
     * @param TokenStorageInterface&MockObject $tokenStorage
     * @param UserProviderInterface&MockObject $userProvider
     * @param LoggerInterface&MockObject       $logger
     */
    private function getAuthenticator(SessionInterface $session = null, TokenStorageInterface $tokenStorage = null, UserProviderInterface $userProvider = null, LoggerInterface $logger = null): FrontendPreviewAuthenticator
    {
        if (null === $session) {
            $session = $this->createMock(SessionInterface::class);
            $session
                ->method('isStarted')
                ->willReturn(true)
            ;
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
        }

        if (null === $userProvider) {
            $userProvider = $this->createMock(UserProviderInterface::class);
        }

        if (null === $logger) {
            $logger = $this->createMock(LoggerInterface::class);
        }

        return new FrontendPreviewAuthenticator($session, $tokenStorage, $userProvider, $logger);
    }
}
