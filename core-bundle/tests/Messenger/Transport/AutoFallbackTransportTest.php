<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger\Transport;

use Contao\CoreBundle\Messenger\AutoFallbackNotifier;
use Contao\CoreBundle\Messenger\Transport\AutoFallbackTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AutoFallbackTransportTest extends TestCase
{
    /**
     * @dataProvider isRunning
     */
    public function testTransportPassesOnCallsCorrectly(bool $isRunning): void
    {
        $notifier = $this->createMock(AutoFallbackNotifier::class);
        $notifier
            ->expects($this->exactly(4))
            ->method('isWorkerRunning')
            ->with('foobar')
            ->willReturn($isRunning)
        ;

        $target = $this->mockTransport($isRunning);
        $fallback = $this->mockTransport(!$isRunning);

        $transport = new AutoFallbackTransport(
            $notifier,
            'foobar',
            $target,
            $fallback
        );

        $transport->get();
        $transport->ack(new Envelope(new \stdClass()));
        $transport->reject(new Envelope(new \stdClass()));
        $transport->send(new Envelope(new \stdClass()));
    }

    public function isRunning(): \Generator
    {
        yield [true];
        yield [false];
    }

    private function mockTransport(bool $expectCalls): TransportInterface
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport
            ->expects($expectCalls ? $this->once() : $this->never())
            ->method('get')
        ;
        $transport
            ->expects($expectCalls ? $this->once() : $this->never())
            ->method('ack')
        ;
        $transport
            ->expects($expectCalls ? $this->once() : $this->never())
            ->method('reject')
        ;
        $transport
            ->expects($expectCalls ? $this->once() : $this->never())
            ->method('send')
            ->willReturn(new Envelope(new \stdClass()))
        ;

        return $transport;
    }
}
