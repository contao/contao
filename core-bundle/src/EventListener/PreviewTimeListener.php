<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Renders the front end preview at a simulated time. We replace the clock
 * globally because legacy classes like Date and Model are static.
 *
 * @internal
 */
class PreviewTimeListener
{
    private ClockInterface|null $originalClock = null;

    public function __construct(private readonly TokenChecker $tokenChecker)
    {
    }

    /**
     * The priority must be lower than the Symfony route listener because of the
     * _preview request attribute (defaults to 32), and lower than the Symfony
     * firewall listener so the token is available and the authentication (e.g. 2FA)
     * is not affected by the mocked clock (defaults to 8).
     */
    #[AsEventListener(priority: 6)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->restoreClock();

        if (!$previewTime = $this->tokenChecker->getPreviewTime()) {
            return;
        }

        // Keep current clock as the mocked one is stopping time for the whole request
        $this->originalClock = Clock::get();

        Clock::set(new MockClock($previewTime));
    }

    private function restoreClock(): void
    {
        if (!$this->originalClock) {
            return;
        }

        Clock::set($this->originalClock);

        $this->originalClock = null;
    }
}
