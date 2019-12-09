<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

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
     * @param string|int $id
     */
    public function __construct(string $key, $id)
    {
        $this->key = $key;
        $this->id = $id;
    }

    /**
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }
}
