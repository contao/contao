<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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
     */
    public function add(string $header): void;

    /**
     * Clears the storage.
     */
    public function clear(): void;
}
