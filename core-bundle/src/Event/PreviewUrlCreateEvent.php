<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

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
     * @param string $key
     * @param int    $id
     */
    public function __construct(string $key, int $id)
    {
        $this->key = $key;
        $this->id = $id;
    }

    /**
     * Returns the ID.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns the module key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Returns the query string.
     *
     * @return string|null
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * Sets the query string.
     *
     * @param string $query
     */
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }
}
