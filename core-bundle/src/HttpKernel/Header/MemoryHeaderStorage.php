<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\HttpKernel\Header;

/**
 * Handles HTTP headers in memory (for unit tests).
 *
 * @internal
 */
class MemoryHeaderStorage implements HeaderStorageInterface
{
    public function __construct(private array $headers = [])
    {
    }

    public function all(): array
    {
        return $this->headers;
    }

    public function add(string $header): void
    {
        $this->headers[] = $header;
    }

    public function clear(): void
    {
        $this->headers = [];
    }
}
