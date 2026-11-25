<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Preview;

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\Clock\ClockInterface;

readonly class PreviewClock
{
    public function __construct(
        private TokenChecker $tokenChecker,
        private ClockInterface $clock,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->tokenChecker->getPreviewTime() ?? $this->clock->now();
    }
}
