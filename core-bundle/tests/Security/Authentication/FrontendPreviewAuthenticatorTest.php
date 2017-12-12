<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\User\FrontendUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class FrontendPreviewAuthenticatorTest extends TestCase
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var FrontendUserProvider
     */
    private $userProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var FrontendUser
     */
    private $user;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->createSessionMock();
    }

    public function testCanBeInstantiated(): void
    {
        $this->mockRequestStack();
        $this->createTokenStorageMock();
        $this->mockLogger();
        $this->mockUserProvider();

        $authenticator = new FrontendPreviewAuthenticator(
            $this->requestStack,
            $this->session,
            $this->tokenStorage,
            $this->userProvider,
            $this->logger
        );

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator', $authenticator);
    }

    public function testReturnsImmediatelyIfNotAuthenticated(): void
    {
        $this->mockRequestStack();
        $this->createTokenStorageMock();
        $this->mockLogger();
        $this->mockUserProvider();

        $authenticator = new FrontendPreviewAuthenticator(
            $this->requestStack,
            $this->session,
            $this->tokenStorage,
            $this->userProvider,
            $this->logger
        );

        $authenticator->authenticateFrontendUser();
    }

    public function testReturnsImmediatelyIfNoUsernameIsGiven(): void
    {
        $this->mockRequestStack();
        $this->createTokenStorageMock(true);
        $this->mockLogger();
        $this->mockUserProvider();

        $authenticator = new FrontendPreviewAuthenticator(
            $this->requestStack,
            $this->session,
            $this->tokenStorage,
            $this->userProvider,
            $this->logger
        );

        $authenticator->authenticateFrontendUser();
    }

    public function testReturnsImmediatelyIfThereIsNoSession(): void
    {
        $this->mockRequestStack();
        $this->createTokenStorageMock(true);
        $this->mockLogger();
        $this->mockUserProvider();

        $authenticator = new FrontendPreviewAuthenticator(
            $this->requestStack,
            $this->session,
            $this->tokenStorage,
            $this->userProvider,
            $this->logger
        );

        $authenticator->authenticateFrontendUser('username');
    }

    public function testFailsIfTheUserIsInvalid(): void
    {
        $this->mockRequestStack(true);
        $this->createTokenStorageMock(true);
        $this->mockLogger('Could not find a front end user with the username "username"');
        $this->mockUserProvider(false);

        $authenticator = new FrontendPreviewAuthenticator(
            $this->requestStack,
            $this->session,
            $this->tokenStorage,
            $this->userProvider,
            $this->logger
        );

        $authenticator->authenticateFrontendUser('username');
    }

    public function testRemovesTheSessionKeyWhenTryingToAuthenticateWithoutARole(): void
    {
        $this->mockRequestStack(true);
        $this->createTokenStorageMock(true);
        $this->mockLogger();
        $this->mockUserProvider(true);

        $authenticator = new FrontendPreviewAuthenticator(
            $this->requestStack,
            $this->session,
            $this->tokenStorage,
            $this->userProvider,
            $this->logger
        );

        $authenticator->authenticateFrontendUser('username');
    }

    public function testAuthenticatesTheUser(): void
    {
        $sessionKey = '_security_contao_frontend';

        $this->mockRequestStack(true);
        $this->createTokenStorageMock(true);
        $this->mockLogger();
        $this->mockUserProvider(true, true);

        $authenticator = new FrontendPreviewAuthenticator(
            $this->requestStack,
            $this->session,
            $this->tokenStorage,
            $this->userProvider,
            $this->logger
        );

        $authenticator->authenticateFrontendUser('username');

        $this->assertTrue(\strlen($this->session->get($sessionKey)) > 0);

        /** @var UsernamePasswordToken $token */
        $token = unserialize(
            $this->session->get($sessionKey),
            ['allowed_classes' => [UsernamePasswordToken::class]]
        );

        $this->assertInstanceOf(UsernamePasswordToken::class, $token);
        $this->assertTrue($token->isAuthenticated());
    }

    /**
     * Mocks the TokenStorage service.
     *
     * @param bool|null $expectedAuthentication
     */
    private function createTokenStorageMock(bool $expectedAuthentication = null): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);

        if (null !== $expectedAuthentication) {
            $this->token
                ->expects($this->once())
                ->method('isAuthenticated')
                ->willReturn($expectedAuthentication)
            ;

            $this->tokenStorage
                ->expects($this->exactly(2))
                ->method('getToken')
                ->willReturn($this->token)
            ;
        }
    }

    /**
     * Mocks the logger service with an optional message.
     *
     * @param string|null $message
     */
    private function mockLogger(string $message = null): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        if (null === $message) {
            $this->logger
                ->expects($this->never())
                ->method('info')
            ;
        }

        if (null !== $message) {
            $context = [
                'contao' => new ContaoContext(
                    'Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator::authenticateFrontendUser',
                    ContaoContext::ACCESS
                ),
            ];

            $this->logger
                ->expects($this->once())
                ->method('info')
                ->with($message, $context)
            ;
        }
    }

    /**
     * Mocks a UserProvider optionally with a valid user and a role.
     *
     * @param bool|null $withValidUser
     * @param bool      $withRole
     */
    private function mockUserProvider(bool $withValidUser = null, bool $withRole = false): void
    {
        $this->userProvider = $this->createMock(FrontendUserProvider::class);

        if (false === $withValidUser) {
            $exception = new UsernameNotFoundException(
                sprintf(
                    'FrontendUser with Username %s could not be found. Frontend authentication aborted.',
                    'username'
                )
            );

            $this->userProvider
                ->expects($this->once())
                ->method('loadUserByUsername')
                ->willThrowException($exception)
            ;
        }

        if (true === $withValidUser) {
            $this->mockFrontendUser($withRole);

            $this->userProvider
                ->expects($this->once())
                ->method('loadUserByUsername')
                ->willReturn($this->user)
            ;
        }
    }

    /**
     * Mocks the FrontendUser with an optional username.
     *
     * @param bool $withRole
     */
    private function mockFrontendUser(bool $withRole = false): void
    {
        $this->user = $this
            ->getMockBuilder(FrontendUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRoles'])
            ->getMock()
        ;

        if (true === $withRole) {
            $this->user
                ->expects($this->any())
                ->method('getRoles')
                ->willReturn(['ROLE_MEMBER'])
            ;
        }
    }

    /**
     * Mocks the RequestStack optionally with a session.
     *
     * @param bool $withSession
     */
    private function mockRequestStack(bool $withSession = false): void
    {
        $this->requestStack = new RequestStack();
        $request = Request::create('https://www.contao.org');

        if (true === $withSession) {
            $request->setSession($this->session);
        }

        $this->requestStack->push($request);
    }

    /**
     * Mocks a Session.
     */
    private function createSessionMock(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $this->session->setId('test-id');
    }
}
