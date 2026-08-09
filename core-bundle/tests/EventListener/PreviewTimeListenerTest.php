<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\PreviewTimeListener;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class PreviewTimeListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Clock::set(new NativeClock());

        parent::tearDown();
    }

    public function testMocksTheClockIfAPreviewTimeIsSet(): void
    {
        $listener = new PreviewTimeListener($this->createTokenCheckerStub(new \DateTimeImmutable('@637974000')));
        $listener->onKernelRequest($this->getRequestEvent());

        $this->assertInstanceOf(MockClock::class, Clock::get());
        $this->assertSame(637974000, Clock::get()->now()->getTimestamp());
    }

    public function testDoesNotMockTheClockWithoutAPreviewTime(): void
    {
        $listener = new PreviewTimeListener($this->createTokenCheckerStub(null));
        $listener->onKernelRequest($this->getRequestEvent());

        $this->assertNotInstanceOf(MockClock::class, Clock::get());
    }

    public function testDoesNotMockTheClockInASubRequest(): void
    {
        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->never())
            ->method('getPreviewTime')
        ;

        $listener = new PreviewTimeListener($tokenChecker);
        $listener->onKernelRequest($this->getRequestEvent(HttpKernelInterface::SUB_REQUEST));

        $this->assertNotInstanceOf(MockClock::class, Clock::get());
    }

    public function testDoesNotRestoreAClockThatWasNeverReplaced(): void
    {
        $originalClock = new NativeClock();
        Clock::set($originalClock);

        $listener = new PreviewTimeListener($this->createTokenCheckerStub(null));
        $listener->onKernelRequest($this->getRequestEvent());

        $this->assertSame($originalClock, Clock::get());
    }

    public function testMockedClockTimeDoesNotAdvance(): void
    {
        $listener = new PreviewTimeListener($this->createTokenCheckerStub(new \DateTimeImmutable('@637974000')));
        $listener->onKernelRequest($this->getRequestEvent());

        $clock = Clock::get();
        $time = $clock->now();

        usleep(1000);

        $this->assertSame($time->getTimestamp(), $clock->now()->getTimestamp());
    }

    private function getRequestEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createStub(KernelInterface::class), new Request(), $requestType);
    }

    private function createTokenCheckerStub(\DateTimeImmutable|null $previewTime): TokenChecker&Stub
    {
        $tokenChecker = $this->createStub(TokenChecker::class);
        $tokenChecker
            ->method('getPreviewTime')
            ->willReturn($previewTime)
        ;

        return $tokenChecker;
    }
}
