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
    private string|null $query = null;

    public function __construct(
        private readonly string $key,
        private readonly int|string $id,
    ) {
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getQuery(): string|null
    {
        return $this->query;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }
}
