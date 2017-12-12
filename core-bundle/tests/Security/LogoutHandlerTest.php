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

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Security\LogoutHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LogoutHandlerTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var TokenInterface
     */
    private $token;

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
        $this->request = new Request();
        $this->response = new Response();
    }

    public function testCanBeInstantiated(): void
    {
        $this->mockLogger();
        $handler = new LogoutHandler($this->framework, $this->logger);

        $this->assertInstanceOf('Contao\CoreBundle\Security\LogoutHandler', $handler);
    }

    public function testReturnsImmediatelyIfThereIsNoUser(): void
    {
        $this->mockLogger();
        $this->mockToken();

        $handler = new LogoutHandler($this->framework, $this->logger);
        $handler->logout($this->request, $this->response, $this->token);
    }

    public function testAddsALogEntryIfThereIsAValidUser(): void
    {
        $this->mockLogger('User "username" has logged out');
        $this->mockToken(true);

        $handler = new LogoutHandler($this->framework, $this->logger);
        $handler->logout($this->request, $this->response, $this->token);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the postLogout hook has been deprecated %s.
     */
    public function testExecutesThePostLogoutHook(): void
    {
        $this->framework
            ->expects($this->once())
            ->method('createInstance')
            ->willReturn($this)
        ;

        $GLOBALS['TL_HOOKS'] = [
            'postLogout' => [[\get_class($this), 'executePostLogoutHookCallback']],
        ];

        $this->mockLogger('User "username" has logged out');
        $this->mockToken(true);

        $handler = new LogoutHandler($this->framework, $this->logger);
        $handler->logout($this->request, $this->response, $this->token);
    }

    /**
     * @param User $user
     */
    public static function executePostLogoutHookCallback(User $user): void
    {
        self::assertInstanceOf('Contao\User', $user);
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
                    'Contao\CoreBundle\Security\LogoutHandler::logout',
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
     * Mocks a Token with an optional valid user.
     *
     * @param bool $validUser
     */
    private function mockToken(bool $validUser = false): void
    {
        $this->token = $this->createMock(TokenInterface::class);

        $user = null;

        if ($validUser) {
            $user = $this->mockUser('username');
        }

        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;
    }

    /**
     * Mocks the User with an optional username.
     *
     * @param string|null $expectedUsername
     *
     * @return User
     */
    private function mockUser(string $expectedUsername = null): User
    {
        /** @var User|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(User::class, ['getUsername']);

        if (null !== $expectedUsername) {
            $user->username = $expectedUsername;
            $user
                ->expects($this->once())
                ->method('getUsername')
                ->willReturn($expectedUsername)
            ;
        }

        return $user;
    }
}
