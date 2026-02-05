<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Session;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Session\SessionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionFactory as SymfonySessionFactory;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionFactoryTest extends TestCase
{
    public function testRegistersTheFrontendAndBackendBag(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->exactly(2))
            ->method('registerBag')
            ->with($this->callback(
                static fn (SessionBagInterface $bag): bool => match ($bag->getStorageKey()) {
                    '_contao_fe_attributes', '_contao_be_attributes' => true,
                    default => false,
                },
            ))
        ;

        $inner = $this->createMock(SymfonySessionFactory::class);
        $inner
            ->expects($this->once())
            ->method('createSession')
            ->willReturn($session)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn(Request::create('/contao'))
        ;

        (new SessionFactory($inner, $requestStack, $scopeMatcher))->createSession();
    }

    public function testRegistersTheFrontendAndBackendPopupBag(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->exactly(2))
            ->method('registerBag')
            ->with($this->callback(
                static fn (SessionBagInterface $bag): bool => match ($bag->getStorageKey()) {
                    '_contao_fe_attributes', '_contao_be_attributes_popup' => true,
                    default => false,
                },
            ))
        ;

        $inner = $this->createMock(SymfonySessionFactory::class);
        $inner
            ->expects($this->once())
            ->method('createSession')
            ->willReturn($session)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn(Request::create('/contao?popup=1'))
        ;

        (new SessionFactory($inner, $requestStack, $scopeMatcher))->createSession();
    }
}
