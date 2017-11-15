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
    public function add($header)
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
