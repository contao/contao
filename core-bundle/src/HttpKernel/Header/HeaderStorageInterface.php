<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\HttpKernel\Header;

/**
 * Interface for HTTP header storage.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface HeaderStorageInterface
{
    /**
     * Returns all headers.
     *
     * @return array
     */
    public function all();

    /**
     * Adds a header to the storage.
     *
     * @param string $header
     * @param bool   $replace
     */
    public function add($header, $replace = true);

    /**
     * Clears the storage.
     */
    public function clear();
}
