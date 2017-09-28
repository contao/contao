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
 * Handles HTTP headers in PHP's native methods.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class NativeHeaderStorage implements HeaderStorageInterface
{
    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return headers_list();
    }

    /**
     * {@inheritdoc}
     */
    public function add($header)
    {
        header($header);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if ('cli' !== PHP_SAPI && !headers_sent()) {
            header_remove();
        }
    }
}
