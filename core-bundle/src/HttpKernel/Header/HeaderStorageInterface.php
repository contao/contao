<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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
     */
    public function add($header);

    /**
     * Clears the storage.
     */
    public function clear();
}
