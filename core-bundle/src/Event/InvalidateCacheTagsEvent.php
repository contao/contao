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

class InvalidateCacheTagsEvent
{
    /**
     * @param list<string> $tags
     */
    public function __construct(private readonly array $tags)
    {
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}
