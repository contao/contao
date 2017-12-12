<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\Authentication\Provider;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Security\Authentication\Provider\ContaoAuthenticationProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ContaoAuthenticationProviderTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var UserCheckerInterface
     */
    private $userChecker;

    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var bool
     */
    private $hideUserNotFoundExceptions;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var UsernamePasswordToken
     */
    private $token;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        unset($GLOBALS['TL_HOOKS']);

        $this->framework = $this->mockContaoFramework();
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->userChecker = $this->createMock(UserCheckerInterface::class);
        $this->providerKey = 'contao_frontend';
        $this->encoderFactory = $this->createMock(EncoderFactoryInterface::class);
        $this->hideUserNotFoundExceptions = false;

        $this->createSessionMock();
        $this->mockLogger();
        $this->mockTranslator();
    }

    public function testCanBeInstantiated(): void
    {
        $authenticationProvider = $this->getProvider();

        $this->assertInstanceOf(
            'Contao\CoreBundle\Security\Authentication\Provider\ContaoAuthenticationProvider',
            $authenticationProvider
        );
    }

    public function testFailsIfTheUserIsInvalid(): void
    {
        $this->mockUser();
        $this->mockToken();

        $encoder = $this->createMock(PasswordEncoderInterface::class);

        $encoder
            ->expects($this->once())
            ->method('isPasswordValid')
            ->willReturn(false)
        ;

        $authenticationProvider = $this->getProvider(null, null, $encoder);

        $this->expectException(BadCredentialsException::class);

        $authenticationProvider->checkAuthentication($this->user, $this->token);
    }

    public function testFailsIfAFrontendUserEntersAnInvalidPassword(): void
    {
        $this->providerKey = 'contao_frontend';
        $this->mockUser(FrontendUser::class, 'foobar');
        $this->mockToken(false, 'foobar', '');
        $this->mockTranslator(true);
        $this->mockEncoder();
        $this->mockFlashBag('contao.FE.error');
        $this->createSessionMock(true);
        $this->mockLogger('Invalid password submitted for username "foobar"');

        $authenticationProvider = $this->getProvider(null, null, $this->encoder);

        $this->expectException(BadCredentialsException::class);

        $authenticationProvider->checkAuthentication($this->user, $this->token);
    }

    public function testFailsIfABackendUserEntersAnInvalidPassword(): void
    {
        $this->providerKey = 'contao_backend';
        $this->mockUser(BackendUser::class, 'foobar');
        $this->mockToken(false, 'foobar', '');
        $this->mockTranslator(true);
        $this->mockEncoder();
        $this->mockFlashBag('contao.BE.error');
        $this->createSessionMock(true);
        $this->mockLogger('Invalid password submitted for username "foobar"');

        $authenticationProvider = $this->getProvider(null, null, $this->encoder);

        $this->expectException(BadCredentialsException::class);

        $authenticationProvider->checkAuthentication($this->user, $this->token);
    }

    public function testAuthenticatesBackendUsers(): void
    {
        $this->providerKey = 'contao_backend';
        $this->mockUser(BackendUser::class);
        $this->mockEncoder(true);
        $this->mockToken(true);

        $authenticationProvider = $this->getProvider(null, null, $this->encoder);
        $authenticationProvider->checkAuthentication($this->user, $this->token);
    }

    public function testAuthenticatesFrontendUsers(): void
    {
        $this->providerKey = 'contao_frontend';
        $this->mockUser(BackendUser::class);
        $this->mockEncoder(true);
        $this->mockToken(true);

        $authenticationProvider = $this->getProvider(null, null, $this->encoder);
        $authenticationProvider->checkAuthentication($this->user, $this->token);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the checkCredentials hook has been deprecated %s.
     */
    public function testAuthenticatesAUserIfTheCheckCredentialsHookReturnsTrue(): void
    {
        $this->framework
            ->expects($this->once())
            ->method('createInstance')
            ->willReturn($this)
        ;

        $GLOBALS['TL_HOOKS'] = [
            'checkCredentials' => [[\get_class($this), 'executeCheckCredentialsHookCallbackReturnsTrue']],
        ];

        $this->providerKey = 'contao_backend';
        $this->mockUser(BackendUser::class);
        $this->mockEncoder(false);
        $this->mockToken(false, 'username', 'password');

        $authenticationProvider = $this->getProvider(null, null, $this->encoder);
        $authenticationProvider->checkAuthentication($this->user, $this->token);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the checkCredentials hook has been deprecated %s.
     */
    public function testFailsToAuthenticateAUserIfTheCheckCredentialsHookReturnsFalse(): void
    {
        $this->framework
            ->expects($this->once())
            ->method('createInstance')
            ->willReturn($this)
        ;

        $GLOBALS['TL_HOOKS'] = [
            'checkCredentials' => [[\get_class($this), 'executeCheckCredentialsHookCallbackReturnsFalse']],
        ];

        $this->providerKey = 'contao_backend';
        $this->mockUser(BackendUser::class, 'username');
        $this->mockToken(false, 'username', 'password');
        $this->mockEncoder(false);
        $this->mockFlashBag('contao.BE.error');
        $this->mockTranslator(true);
        $this->createSessionMock(true);
        $this->mockLogger('Invalid password submitted for username "username"');

        $authenticationProvider = $this->getProvider(null, null, $this->encoder);

        $this->expectException(BadCredentialsException::class);

        $authenticationProvider->checkAuthentication($this->user, $this->token);
    }

    /**
     * checkCredentials hook stub.
     *
     * @param string $username
     * @param string $credentials
     * @param User   $user
     *
     * @return bool
     */
    public static function executeCheckCredentialsHookCallbackReturnsTrue(string $username, string $credentials, User $user): bool
    {
        self::assertSame('username', $username);
        self::assertSame('password', $credentials);
        self::assertInstanceOf(BackendUser::class, $user);

        return true;
    }

    /**
     * checkCredentials hook stub.
     *
     * @param string $username
     * @param string $credentials
     * @param User   $user
     *
     * @return bool
     */
    public static function executeCheckCredentialsHookCallbackReturnsFalse(string $username, string $credentials, User $user): bool
    {
        self::assertSame('username', $username);
        self::assertSame('password', $credentials);
        self::assertInstanceOf(BackendUser::class, $user);

        return false;
    }

    /**
     * Mocks the User with an optional username.
     *
     * @param string      $class
     * @param string|null $expectedUsername
     */
    private function mockUser(string $class = null, string $expectedUsername = null): void
    {
        if (null === $class) {
            $this->user = $this->createMock(UserInterface::class);
        } else {
            $this->user = $this->createPartialMock($class, ['getUsername', 'save']);
        }

        if (null !== $expectedUsername) {
            $this->user->username = $expectedUsername;

            $this->user
                ->expects($this->once())
                ->method('getUsername')
                ->willReturn($expectedUsername)
            ;
        }
    }

    /**
     * Mocks a Token.
     *
     * @param bool        $supported
     * @param string|null $username
     * @param string|null $credentials
     */
    private function mockToken(bool $supported = false, string $username = null, string $credentials = null): void
    {
        $this->token = $this->createPartialMock(
            UsernamePasswordToken::class,
            ['getCredentials', 'getUser', 'getProviderKey', 'getUsername']
        );

        if (true === $supported) {
            $this->token
                ->expects($this->any())
                ->method('getProviderKey')
                ->willReturn('key')
            ;

            $this->token
                ->expects($this->once())
                ->method('getCredentials')
                ->willReturn('foo')
            ;
        }

        if (null !== $username) {
            $this->token
                ->method('getUsername')
                ->willReturn($username)
            ;
        }

        if (null !== $credentials) {
            $this->token
                ->method('getCredentials')
                ->willReturn($credentials)
            ;
        }
    }

    /**
     * Returns a ContaoAuthenticationProvider.
     *
     * @param User|null                     $user
     * @param UserCheckerInterface|null     $userChecker
     * @param PasswordEncoderInterface|null $passwordEncoder
     *
     * @return ContaoAuthenticationProvider
     */
    private function getProvider(User $user = null, UserCheckerInterface $userChecker = null, PasswordEncoderInterface $passwordEncoder = null): ContaoAuthenticationProvider
    {
        $userProvider = $this->createMock(UserProviderInterface::class);

        if (null !== $user) {
            $userProvider
                ->expects($this->once())
                ->method('loadUserByUsername')
                ->willReturn($user)
            ;
        }

        if (null === $userChecker) {
            $userChecker = $this->createMock(UserCheckerInterface::class);
        }

        if (null === $passwordEncoder) {
            $passwordEncoder = new PlaintextPasswordEncoder();
        }

        $encoderFactory = $this->createMock(EncoderFactoryInterface::class);

        $encoderFactory
            ->expects($this->any())
            ->method('getEncoder')
            ->willReturn($passwordEncoder)
        ;

        return new ContaoAuthenticationProvider(
            $userProvider,
            $userChecker,
            $this->providerKey,
            $encoderFactory,
            $this->hideUserNotFoundExceptions,
            $this->session,
            $this->translator,
            $this->framework,
            $this->logger
        );
    }

    /**
     * Mocks the logger service with an optional message.
     *
     * @param string|null $message
     */
    private function mockLogger(string $message = null): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        if (null !== $message) {
            $context = [
                'contao' => new ContaoContext(
                    'Contao\CoreBundle\Security\Authentication\Provider\ContaoAuthenticationProvider::checkAuthentication',
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
     * Mocks a translator with an optional translation.
     *
     * @param bool $withTranslation
     */
    private function mockTranslator(bool $withTranslation = false): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        if (true === $withTranslation) {
            $this->translator
                ->expects($this->once())
                ->method('trans')
                ->with('ERR.invalidLogin', [], 'contao_default')
                ->willReturn('Login failed (note that usernames and passwords are case-sensitive)!')
            ;
        }
    }

    /**
     * Mocks an encoder.
     *
     * @param bool|null $isPasswordValid
     */
    private function mockEncoder(bool $isPasswordValid = null): void
    {
        $this->encoder = $this->createMock(PasswordEncoderInterface::class);

        if (null !== $isPasswordValid) {
            $this->encoder
                ->expects($this->once())
                ->method('isPasswordValid')
                ->willReturn($isPasswordValid)
            ;
        }
    }

    /**
     * Mocks a flashBag.
     *
     * @param string|null $type
     */
    private function mockFlashBag(string $type = null): void
    {
        $this->flashBag = $this->createMock(FlashBagInterface::class);

        if (null !== $type) {
            $this->flashBag
                ->expects($this->once())
                ->method('set')
                ->with($type, 'Login failed (note that usernames and passwords are case-sensitive)!')
            ;
        }
    }

    /**
     * Mocks a session mock with an optional flashBag.
     *
     * @param bool $withFlashBag
     */
    private function createSessionMock(bool $withFlashBag = false): void
    {
        $this->session = $this->createMock(Session::class);

        if (true === $withFlashBag) {
            $this->session
                ->expects($this->once())
                ->method('getFlashBag')
                ->willReturn($this->flashBag)
            ;
        }
    }
}
