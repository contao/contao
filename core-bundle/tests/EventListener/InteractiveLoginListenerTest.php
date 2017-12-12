<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\User;

use Contao\CoreBundle\EventListener\InteractiveLoginListener;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Tests\TestCase;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class InteractiveLoginListenerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        unset($GLOBALS['TL_HOOKS']);
    }

    public function testCanBeInstantiated(): void
    {
        $logger = $this->mockLogger();
        $listener = new InteractiveLoginListener($this->mockContaoFramework(), $logger);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\InteractiveLoginListener', $listener);
    }

    public function testReturnsImmediatelyIfThereIsNoUser(): void
    {
        $logger = $this->mockLogger();
        $event = $this->mockInteractiveLoginEvent();

        $listener = new InteractiveLoginListener($this->mockContaoFramework(), $logger);
        $listener->onInteractiveLogin($event);
    }

    public function testAddsALogEntryIfAValidUserIsGiven(): void
    {
        $logger = $this->mockLogger('User "username" has logged in');
        $event = $this->mockInteractiveLoginEvent('username');

        $listener = new InteractiveLoginListener($this->mockContaoFramework(), $logger);
        $listener->onInteractiveLogin($event);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the "postLogin" hook has been deprecated %s.
     */
    public function testExecutesThePostLoginHook(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->willReturn($this)
        ;

        $GLOBALS['TL_HOOKS'] = [
            'postLogin' => [[\get_class($this), 'executePostLoginHookCallback']],
        ];

        $logger = $this->mockLogger('User "username" has logged in');
        $event = $this->mockInteractiveLoginEvent('username');

        $listener = new InteractiveLoginListener($framework, $logger);
        $listener->onInteractiveLogin($event);
    }

    /**
     * @param User $user
     */
    public static function executePostLoginHookCallback(User $user): void
    {
        self::assertInstanceOf('Contao\User', $user);
    }

    /**
     * Mocks a logger service with an optional message.
     *
     * @param string|null $message
     *
     * @return LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockLogger(string $message = null): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);

        if (null === $message) {
            $logger
                ->expects($this->never())
                ->method('info')
            ;

            return $logger;
        }

        $context = [
            'contao' => new ContaoContext(
                'Contao\CoreBundle\EventListener\InteractiveLoginListener::onInteractiveLogin',
                ContaoContext::ACCESS
            ),
        ];

        $logger
            ->expects($this->once())
            ->method('info')
            ->with($message, $context)
        ;

        return $logger;
    }

    /**
     * Mocks an interactive login event with an optional target username.
     *
     * @param string|null $username
     *
     * @return InteractiveLoginEvent
     */
    private function mockInteractiveLoginEvent(string $username = null): InteractiveLoginEvent
    {
        return new InteractiveLoginEvent(new Request(), $this->mockToken($username));
    }

    /**
     * Mocks a token with an optional username.
     *
     * @param string|null $username
     *
     * @return TokenInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockToken(string $username = null): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);

        if (null !== $username) {
            $user = $this->createPartialMock(User::class, ['getUsername', 'save']);

            $user
                ->expects($this->once())
                ->method('getUsername')
                ->willReturn($username)
            ;

            $user
                ->expects($this->once())
                ->method('save')
            ;

            $token
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($user)
            ;
        }

        return $token;
    }
}
