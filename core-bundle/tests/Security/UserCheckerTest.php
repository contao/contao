<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\UserChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UserCheckerTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        unset($GLOBALS['TL_CONFIG']);

        $this->framework = $this->mockContaoFramework();
        $this->mockLogger();
        $this->mockTranslator();
        $this->mockMailer();
        $this->createSessionMock();
        $this->scopeMatcher = $this->mockScopeMatcher();
        $this->mockRequestStack();
    }

    public function testCanBeInstantiated(): void
    {
        $userChecker = $this->getUserChecker();

        $this->assertInstanceOf('Contao\CoreBundle\Security\UserCHecker', $userChecker);
    }

    public function testReturnsImmediatelyIfNoContaoUserIsGiven(): void
    {
        /** @var UserCheckerInterface|\PHPUnit_Framework_MockObject_MockObject $userChecker */
        $userChecker = $this->createPartialMock(UserChecker::class, ['checkLoginAttempts']);
        $user = $this->mockUser();

        $userChecker
            ->expects($this->never())
            ->method('checkLoginAttempts')
        ;

        $userChecker->checkPreAuth($user);
    }

    public function testChecksTheAccountAfterAuthentication(): void
    {
        $user = $this->mockUser();

        $userChecker = $this->getUserChecker();
        $userChecker->checkPostAuth($user);
    }

    public function testChecksTheAccountBeforeAuthentication(): void
    {
        $user = $this->mockUser(BackendUser::class, 3);

        $userChecker = $this->getUserChecker();
        $userChecker->checkPreAuth($user);
    }

    public function testThrowsALockedExceptionIfTheFrontendLoginCountIsZero(): void
    {
        $parameters = [
            'foobar',
            'foo bar',
            'https://www.contao.org',
            5,
        ];

        $this->setGlobals('Y-m-d', 300, 'mail@example.com');

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator
            ->expects($this->exactly(3))
            ->method('trans')
            ->withConsecutive(
                ['ERR.accountLocked', [5], 'contao_default'],
                ['MSC.lockedAccount.0', [], 'contao_default'],
                ['MSC.lockedAccount.1', $parameters, 'contao_default']
            )
            ->willReturnOnConsecutiveCalls(
                'This account has been locked! You can log in again in 5 minutes.',
                'A Contao account has been locked',
                $this->getMailContent('foobar', 'foo bar', 'https://www.contao.org', 5)
            )
        ;

        $this->mockMailer(true);
        $request = $this->mockRequest([], ['_scope' => 'frontend']);
        $user = $this->mockUser(
            FrontendUser::class,
            0,
            null,
            null,
            null,
            true,
            'foobar',
            null,
            null,
            'foo',
            'bar'
        );

        $this->mockLogger('User "foobar" has been locked for 5 minutes', 'checkLoginAttempts');
        $this->mockFlashBag('contao.FE.error', 'This account has been locked! You can log in again in 5 minutes.');
        $this->createSessionMock(true);
        $this->mockRequestStack($request);
        $userChecker = $this->getUserChecker();

        $this->expectException(LockedException::class);

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsALockedExceptionIfTheBackendLoginCountIsZero(): void
    {
        $parameters = [
            'foobar',
            'foo',
            'https://www.contao.org',
            5,
        ];

        $this->setGlobals('Y-m-d', 300, 'mail@example.com');

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator
            ->expects($this->exactly(3))
            ->method('trans')
            ->withConsecutive(
                ['ERR.accountLocked', [5], 'contao_default'],
                ['MSC.lockedAccount.0', [], 'contao_default'],
                ['MSC.lockedAccount.1', $parameters, 'contao_default']
            )
            ->willReturnOnConsecutiveCalls(
                'This account has been locked! You can log in again in 5 minutes.',
                'A Contao account has been locked',
                $this->getMailContent('foobar', 'foo', 'https://www.contao.org', 5)
            )
        ;

        $this->mockMailer(true);
        $request = $this->mockRequest([], ['_scope' => 'backend']);
        $user = $this->mockUser(
            BackendUser::class,
            0,
            null,
            null,
            null,
            true,
            'foobar',
            null,
            null,
            null,
            null,
            'foo'
        );

        $this->mockLogger('User "foobar" has been locked for 5 minutes', 'checkLoginAttempts');
        $this->mockFlashBag('contao.BE.error', 'This account has been locked! You can log in again in 5 minutes.');
        $this->createSessionMock(true);
        $this->mockRequestStack($request);
        $userChecker = $this->getUserChecker();

        $this->expectException(LockedException::class);

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsALockedExceptionIfTheAccountIsLocked(): void
    {
        $this->setGlobals('Y-m-d', 300);

        $request = $this->mockRequest([], ['_scope' => 'backend']);

        $this->mockTranslator(
            true,
            'ERR.accountLocked',
            [5],
            'contao_default',
            'This account has been locked! You can log in again in 5 minutes.'
        );

        $this->mockFlashBag('contao.BE.error', 'This account has been locked! You can log in again in 5 minutes.');
        $this->createSessionMock(true);
        $this->mockRequestStack($request);

        $user = $this->mockUser(BackendUser::class, 3, false, time());
        $userChecker = $this->getUserChecker();

        $this->expectException(LockedException::class);

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsADisabledExceptionIfTheAccountHasBeenDisabled(): void
    {
        $request = $this->mockRequest([], ['_scope' => 'frontend']);

        $this->mockTranslator(
            true,
            'ERR.invalidLogin',
            [],
            'contao_default',
            'Login failed (note that usernames and passwords are case-sensitive)!'
        );

        $this->mockLogger('The account has been disabled', 'checkIfAccountIsDisabled');
        $this->mockFlashBag('contao.FE.error', 'Login failed (note that usernames and passwords are case-sensitive)!');
        $this->createSessionMock(true);
        $this->mockRequestStack($request);

        $user = $this->mockUser(BackendUser::class, 3, null, null, false);
        $userChecker = $this->getUserChecker();

        $this->expectException(DisabledException::class);

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsADisabledExceptionIfTheFrontendLoginIsDisabled(): void
    {
        $request = $this->mockRequest([], ['_scope' => 'frontend']);

        $this->mockTranslator(
            true,
            'ERR.invalidLogin',
            [],
            'contao_default',
            'Login failed (note that usernames and passwords are case-sensitive)!'
        );

        $this->mockFlashBag('contao.FE.error', 'Login failed (note that usernames and passwords are case-sensitive)!');
        $this->createSessionMock(true);
        $this->mockRequestStack($request);
        $this->mockLogger('User "foobar" is not allowed to log in', 'checkIfLoginIsAllowed');

        $user = $this->mockUser(
            FrontendUser::class,
            3,
            null,
            null,
            true,
            false,
            'foobar'
        );

        $userChecker = $this->getUserChecker();

        $this->expectException(DisabledException::class);

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsADisabledExceptionIfTheAccountIsNotActiveYet(): void
    {
        $this->setGlobals('Y-m-d');

        $tomorrow = new \DateTime('tomorrow');
        $request = $this->mockRequest([], ['_scope' => 'frontend']);

        $this->mockTranslator(
            true,
            'ERR.invalidLogin',
            [],
            'contao_default',
            'Login failed (note that usernames and passwords are case-sensitive)!'
        );

        $this->mockFlashBag('contao.FE.error', 'Login failed (note that usernames and passwords are case-sensitive)!');
        $this->createSessionMock(true);
        $this->mockRequestStack($request);
        $this->mockLogger(
            sprintf('The account is not active yet (activation date: %s)', $tomorrow->format('Y-m-d')),
            'checkIfAccountIsActive'
        );

        $user = $this->mockUser(
            FrontendUser::class,
            3,
            null,
            null,
            true,
            true,
            null,
            $tomorrow->getTimestamp()
        );

        $userChecker = $this->getUserChecker();

        $this->expectException(DisabledException::class);

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsADisabledExceptionIfTheAccountIsNotActiveAnymore(): void
    {
        $this->setGlobals('Y-m-d');

        $yesterday = new \DateTime('yesterday');
        $request = $this->mockRequest([], ['_scope' => 'frontend']);

        $this->mockTranslator(
            true,
            'ERR.invalidLogin',
            [],
            'contao_default',
            'Login failed (note that usernames and passwords are case-sensitive)!'
        );

        $this->mockFlashBag('contao.FE.error', 'Login failed (note that usernames and passwords are case-sensitive)!');
        $this->createSessionMock(true);
        $this->mockRequestStack($request);
        $this->mockLogger(
            sprintf('The account is not active anymore (deactivation date: %s)', $yesterday->format('Y-m-d')),
            'checkIfAccountIsActive'
        );

        $user = $this->mockUser(
            FrontendUser::class,
            3,
            null,
            null,
            true,
            true,
            null,
            0,
            $yesterday->getTimestamp()
        );

        $userChecker = $this->getUserChecker();

        $this->expectException(DisabledException::class);
        $userChecker->checkPreAuth($user);
    }

    /**
     * Mocks the logger service with an optional message.
     *
     * @param string|null $message
     * @param string|null $method
     */
    private function mockLogger(string $message = null, string $method = null): void
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
                    sprintf('Contao\CoreBundle\Security\UserChecker::%s', $method),
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
     * @param bool   $withTranslation
     * @param string $key
     * @param array  $params
     * @param string $domain
     * @param string $translated
     */
    private function mockTranslator(bool $withTranslation = false, string $key = '', array $params = [], string $domain = 'contao_default', string $translated = ''): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        if (true === $withTranslation) {
            $this->translator
                ->expects($this->once())
                ->method('trans')
                ->with($key, $params, $domain)
                ->willReturn($translated)
            ;
        }
    }

    /**
     * Mocks the mailer service with an optional send method call.
     *
     * @param bool|null $send
     */
    private function mockMailer(bool $send = null): void
    {
        $this->mailer = $this->createPartialMock(\Swift_Mailer::class, ['send']);

        if (null !== $send) {
            $this->mailer
                ->expects($this->once())
                ->method('send')
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

    /**
     * Mocks a flashBag.
     *
     * @param string $type
     * @param string $message
     */
    private function mockFlashBag(string $type, string $message): void
    {
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->flashBag
            ->expects($this->once())
            ->method('set')
            ->with($type, $message)
        ;
    }

    /**
     * Mocks the request with options, attributes and query parameters.
     *
     * @param array $options
     * @param array $attributes
     * @param array $query
     *
     * @return Request
     */
    private function mockRequest(array $options = [], array $attributes = [], array $query = []): Request
    {
        $request = Request::create('https://www.contao.org');

        foreach ($options as $key => $value) {
            $request->request->set($key, $value);
        }

        foreach ($attributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        foreach ($query as $key => $value) {
            $request->query->set($key, $value);
        }

        return $request;
    }

    /**
     * Mocks a RequestStack with an optional request.
     *
     * @param Request|null $request
     */
    private function mockRequestStack(Request $request = null): void
    {
        $this->requestStack = new RequestStack();

        if (null !== $request) {
            $this->requestStack->push($request);
        }
    }

    /**
     * Mocks a User with optional properties.
     *
     * @param string|null $class
     * @param int|null    $loginCount
     * @param bool|null   $isAccountNonLocked
     * @param int|null    $locked
     * @param bool|null   $isEnabled
     * @param bool|null   $login
     * @param string|null $username
     * @param int|null    $start
     * @param int|null    $stop
     * @param string|null $firstname
     * @param string|null $lastname
     * @param string|null $name
     *
     * @return UserInterface
     */
    private function mockUser(string $class = null, int $loginCount = null, bool $isAccountNonLocked = null, int $locked = null, bool $isEnabled = null, bool $login = null, string $username = null, int $start = null, int $stop = null, string $firstname = null, string $lastname = null, string $name = null): UserInterface
    {
        $user = $this->createMock(UserInterface::class);

        if (null !== $class) {
            $user = $this->createPartialMock(
                $class,
                [
                    'isAccountNonLocked',
                    'isEnabled',
                    'getUsername',
                    'save',
                ]
            );
        }

        if (null !== $loginCount) {
            $user->loginCount = $loginCount;
        }

        if (null !== $isAccountNonLocked) {
            $user
                ->expects($this->once())
                ->method('isAccountNonLocked')
                ->willReturn($isAccountNonLocked)
            ;
        }

        if (null !== $locked) {
            $user->locked = $locked;
        }

        if (null !== $isEnabled) {
            $user
                ->expects($this->once())
                ->method('isEnabled')
                ->willReturn($isEnabled)
            ;
        }

        if (null !== $login) {
            $user->login = $login;
        }

        if (null !== $username) {
            $user->username = $username;
            $user
                ->expects($this->atLeastOnce())
                ->method('getUsername')
                ->willReturn($username)
            ;
        }

        if (null !== $start) {
            $user->start = $start;
        }

        if (null !== $stop) {
            $user->stop = $stop;
        }

        if (null !== $firstname) {
            $user->firstname = $firstname;
        }

        if (null !== $lastname) {
            $user->lastname = $lastname;
        }

        if (null !== $name) {
            $user->name = $name;
        }

        return $user;
    }

    /**
     * Mocks a UserChecker object.
     *
     * @return UserChecker
     */
    private function getUserChecker(): UserChecker
    {
        return new UserChecker(
            $this->translator,
            $this->mailer,
            $this->session,
            $this->scopeMatcher,
            $this->requestStack,
            $this->framework,
            $this->logger
        );
    }

    /**
     * Sets some global variables.
     *
     * @param string|null $dateFormat
     * @param int|null    $lockPeriod
     * @param string|null $adminEmail
     */
    private function setGlobals(string $dateFormat = null, int $lockPeriod = null, string $adminEmail = null): void
    {
        if (null !== $dateFormat) {
            $GLOBALS['TL_CONFIG']['dateFormat'] = $dateFormat;
        }

        if (null !== $lockPeriod) {
            $GLOBALS['TL_CONFIG']['lockPeriod'] = $lockPeriod;
        }

        if (null !== $adminEmail) {
            $GLOBALS['TL_CONFIG']['adminEmail'] = $adminEmail;
            $_SERVER['SERVER_NAME'] = '';
            $_SERVER['SERVER_PORT'] = '';
        }
    }

    /**
     * Returns the mail content.
     *
     * @param string $username
     * @param string $realname
     * @param string $website
     * @param int    $locked
     *
     * @return string
     */
    private function getMailContent(string $username, string $realname, string $website, int $locked): string
    {
        return <<<EOT
The following Contao account has been locked for security reasons.

Username: $username
Real name: $realname
Website: $website

The account has been locked for $locked minutes, because the user has entered an invalid password three times in a row. After this period of time, the account will be unlocked automatically.

This e-mail has been generated by Contao. You can not reply to it directly.
EOT;
    }
}
