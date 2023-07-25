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

use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FrontendPreviewAuthenticatorTest extends TestCase
{
    /**
     * @dataProvider getShowUnpublishedData
     */
    public function testAuthenticatesAFrontendUser(bool $showUnpublished): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturnOnConsecutiveCalls(true)
        ;

        $session = $this->mockSession();

        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_MEMBER'])
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->method('loadUserByIdentifier')
            ->willReturn($user)
        ;

        $authenticator = $this->getAuthenticator($security, null, null, $session, $userProvider);

        $this->assertTrue($authenticator->authenticateFrontendUser('foobar', $showUnpublished));
        $this->assertTrue($session->has('_security_contao_frontend'));
        $this->assertTrue($session->has(FrontendPreviewAuthenticator::SESSION_NAME));

        $token = unserialize($session->get('_security_contao_frontend'), ['allowed_classes' => true]);

        $this->assertInstanceOf(UsernamePasswordToken::class, $token);
        $this->assertInstanceOf(FrontendUser::class, $token->getUser());
        $this->assertSame(['ROLE_MEMBER'], $token->getRoleNames());
        $this->assertSame($showUnpublished, $session->get(FrontendPreviewAuthenticator::SESSION_NAME)['showUnpublished']);
    }

    /**
     * @dataProvider getShowUnpublishedData
     */
    public function testAuthenticatesAFrontendUserViaFirewall(bool $showUnpublished): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturnOnConsecutiveCalls(true)
        ;

        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_MEMBER'])
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->callback(
                static function (UsernamePasswordToken $token) use ($user): bool {
                    return $user === $token->getUser()
                        && ['ROLE_MEMBER'] === $token->getRoleNames();
                }
            ))
        ;

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('isFrontendFirewall')
            ->willReturn(true)
        ;

        $session = $this->mockSession();

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->method('loadUserByIdentifier')
            ->willReturn($user)
        ;

        $authenticator = $this->getAuthenticator($security, $tokenStorage, $tokenChecker, $session, $userProvider);

        $this->assertTrue($authenticator->authenticateFrontendUser('foobar', $showUnpublished));
        $this->assertTrue($session->has(FrontendPreviewAuthenticator::SESSION_NAME));

        $this->assertSame($showUnpublished, $session->get(FrontendPreviewAuthenticator::SESSION_NAME)['showUnpublished']);
    }

    public function getShowUnpublishedData(): \Generator
    {
        yield [true];
        yield [false];
    }

    public function testDoesNotAuthenticateAFrontendUserIfTheUsernameIsInvalid(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willThrowException(new UserNotFoundException())
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Could not find a front end user with the username "foobar"')
        ;

        $authenticator = $this->getAuthenticator($security, null, null, null, $userProvider, $logger);

        $this->assertFalse($authenticator->authenticateFrontendUser('foobar', false));
    }

    public function testDoesNotAuthenticateAFrontendUserIfThereIsNoAllowedMemberGroup(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturnOnConsecutiveCalls(false)
        ;

        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user
            ->expects($this->never())
            ->method('getRoles')
            ->willReturn(['ROLE_MEMBER'])
        ;

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider
            ->method('loadUserByIdentifier')
            ->willReturn($user)
        ;

        $authenticator = $this->getAuthenticator($security, null, null, null, $userProvider);

        $this->assertFalse($authenticator->authenticateFrontendUser('foobar', false));
    }

    /**
     * @dataProvider getShowUnpublishedPreviewLinkIdData
     */
    public function testAuthenticatesAFrontendGuest(bool $showUnpublished, int|null $previewLinkId): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $session = $this->mockSession();
        $authenticator = $this->getAuthenticator($security, null, null, $session);

        $this->assertTrue($authenticator->authenticateFrontendGuest($showUnpublished, $previewLinkId));
        $this->assertFalse($session->has('_security_contao_frontend'));
        $this->assertTrue($session->has(FrontendPreviewAuthenticator::SESSION_NAME));

        $this->assertSame($showUnpublished, $session->get(FrontendPreviewAuthenticator::SESSION_NAME)['showUnpublished']);
        $this->assertSame($previewLinkId, $session->get(FrontendPreviewAuthenticator::SESSION_NAME)['previewLinkId']);
    }

    public function getShowUnpublishedPreviewLinkIdData(): \Generator
    {
        yield [true, null];
        yield [true, 123];
        yield [false, null];
        yield [false, 123];
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
            ->expects($this->atMost(2))
            ->method('has')
            ->withConsecutive(['_security_contao_frontend'], [FrontendPreviewAuthenticator::SESSION_NAME])
            ->willReturn(true)
        ;

        $session
            ->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(['_security_contao_frontend'], [FrontendPreviewAuthenticator::SESSION_NAME])
        ;

        $authenticator = $this->getAuthenticator(null, null, null, $session);

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

        $authenticator = $this->getAuthenticator(null, null, null, $session);

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
            ->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(['_security_contao_frontend'], [FrontendPreviewAuthenticator::SESSION_NAME])
            ->willReturn(false)
        ;

        $authenticator = $this->getAuthenticator(null, null, null, $session);

        $this->assertFalse($authenticator->removeFrontendAuthentication());
    }

    private function getAuthenticator(Security|null $security = null, TokenStorageInterface|null $tokenStorage = null, TokenChecker|null $tokenChecker = null, SessionInterface|null $session = null, UserProviderInterface|null $userProvider = null, LoggerInterface|null $logger = null): FrontendPreviewAuthenticator
    {
        if (null === $session) {
            $session = $this->createMock(SessionInterface::class);
            $session
                ->method('isStarted')
                ->willReturn(true)
            ;
        }

        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $security ??= $this->createMock(Security::class);
        $tokenStorage ??= $this->createMock(TokenStorageInterface::class);
        $tokenChecker ??= $this->createMock(TokenChecker::class);
        $userProvider ??= $this->createMock(UserProviderInterface::class);
        $logger ??= $this->createMock(LoggerInterface::class);

        return new FrontendPreviewAuthenticator($security, $tokenStorage, $tokenChecker, $requestStack, $userProvider, $logger);
    }
}
