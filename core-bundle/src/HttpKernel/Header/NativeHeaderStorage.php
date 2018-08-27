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
    public function add($header, $replace = true)
    {
        header($header, $replace);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if ('cli' !== \PHP_SAPI && !headers_sent()) {
            header_remove();
        }
    }
}
