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
     * @param array<string> $tags
     */
    public function __construct(private array $tags)
    {
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}
