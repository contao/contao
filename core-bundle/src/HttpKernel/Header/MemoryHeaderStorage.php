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
 * Handles HTTP headers in memory (for unit tests).
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class MemoryHeaderStorage implements HeaderStorageInterface
{
    /**
     * @var array
     */
    private $headers;

    /**
     * Constructor.
     *
     * @param array $headers
     */
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function add($header, $replace = true)
    {
        $this->headers[] = $header;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->headers = [];
    }
}
