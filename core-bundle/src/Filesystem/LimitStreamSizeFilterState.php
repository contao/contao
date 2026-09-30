<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

/**
 * @internal
 */
final class LimitStreamSizeFilterState
{
    private int $readBytes = 0;
    private bool $exceeded = false;

    public function __construct(private readonly int $maximumSize)
    {
        if (0 > $maximumSize) {
            throw new \InvalidArgumentException('The maximum stream size must be non-negative.');
        }
    }

    public function consume(int $bytes): bool
    {
        if ($bytes > $this->maximumSize - $this->readBytes) {
            $this->exceeded = true;

            return false;
        }

        $this->readBytes += $bytes;

        return true;
    }

    public function isExceeded(): bool
    {
        return $this->exceeded;
    }
}
