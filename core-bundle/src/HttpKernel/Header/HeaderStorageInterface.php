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
 * @internal
 */
interface HeaderStorageInterface
{
    /**
     * Returns all headers.
     *
     * @return array<string>
     */
    public function all(): array;

    /**
     * Adds a header to the storage.
     */
    public function add(string $header): void;

    /**
     * Clears the storage.
     */
    public function clear(): void;
}
