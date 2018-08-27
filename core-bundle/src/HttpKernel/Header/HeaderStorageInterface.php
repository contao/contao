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

interface HeaderStorageInterface
{
    /**
     * Returns all headers.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Adds a header to the storage.
     *
     * @param string $header
     * @param bool   $replace
     */
    public function add(string $header, bool $replace = true): void;

    /**
     * Clears the storage.
     */
    public function clear(): void;
}
