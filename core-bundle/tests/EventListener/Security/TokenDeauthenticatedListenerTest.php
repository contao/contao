<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Security;

use Contao\CoreBundle\EventListener\Security\TokenDeauthenticatedListener;
use Contao\CoreBundle\Repository\RememberMeRepository;
use Contao\TestCase\ContaoTestCase;
use Contao\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\TokenDeauthenticatedEvent;

class TokenDeauthenticatedListenerTest extends ContaoTestCase
{
    public function testDeletesRelatedRememberMeRecords(): void
    {
        $user = $this->createMock(User::class);
        $user
            ->expects($this->exactly(2))
            ->method('getUserIdentifier')
            ->willReturn('foobar')
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $repository = $this->createMock(RememberMeRepository::class);
        $repository
            ->expects($this->once())
            ->method('deleteByUserIdentifier')
            ->with($user->getUserIdentifier())
        ;

        $event = new TokenDeauthenticatedEvent($token, $this->createMock(Request::class));

        $listener = new TokenDeauthenticatedListener($repository);
        $listener($event);
    }

    public function testDoesNotingIfNoContaoUserPresent(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $repository = $this->createMock(RememberMeRepository::class);

        $event = new TokenDeauthenticatedEvent($token, $this->createMock(Request::class));

        $listener = new TokenDeauthenticatedListener($repository);
        $listener($event);

        $this->expectNotToPerformAssertions();
    }
}
