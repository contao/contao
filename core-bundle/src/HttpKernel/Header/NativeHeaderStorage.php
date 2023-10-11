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
 * Handles HTTP headers in PHP's native methods.
 */
class NativeHeaderStorage implements HeaderStorageInterface
{
    /**
     * @var array<string>
     */
    private array $clearedHeaders = [];

    public function all(): array
    {
        return array_diff(headers_list(), $this->clearedHeaders);
    }

    public function add(string $header): void
    {
        header($header);
    }

    public function clear(): void
    {
        // Keep cleared headers because header_remove() does not reliably clear all the headers depending on the
        // SAPI
        $this->clearedHeaders = array_merge($this->clearedHeaders, headers_list());

        if ('cli' !== \PHP_SAPI && !headers_sent()) {
            header_remove();
        }
    }
}
