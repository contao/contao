<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Preview;

use Contao\CoreBundle\Preview\PreviewClock;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Clock\MockClock;

class PreviewClockTest extends TestCase
{
    public function testReturnsThePreviewTime(): void
    {
        $tokenChecker = $this->createStub(TokenChecker::class);
        $tokenChecker
            ->method('getPreviewTime')
            ->willReturn(new \DateTimeImmutable('@637974000'))
        ;

        $clock = new PreviewClock($tokenChecker, new MockClock('@1700000000'));

        $this->assertSame(637974000, $clock->now()->getTimestamp());
    }

    public function testFallsBackToTheCurrentTime(): void
    {
        $tokenChecker = $this->createStub(TokenChecker::class);
        $tokenChecker
            ->method('getPreviewTime')
            ->willReturn(null)
        ;

        $clock = new PreviewClock($tokenChecker, new MockClock('@1700000000'));

        $this->assertSame(1700000000, $clock->now()->getTimestamp());
    }
}
