<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Allows to create a preview URL.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlCreateEvent extends Event
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $query;

    /**
     * Constructor.
     *
     * @param string $key The module key
     * @param int    $id  The ID
     */
    public function __construct($key, $id)
    {
        $this->key = $key;
        $this->id = $id;
    }

    /**
     * Returns the ID.
     *
     * @return int The ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the module key.
     *
     * @return string The module key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the query string.
     *
     * @return string The query string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Sets the query string.
     *
     * @param string $query The query string
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }
}
