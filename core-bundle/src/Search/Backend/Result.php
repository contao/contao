<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend;

/**
 * @experimental
 */
class Result
{
    /**
     * @param array<Hit> $hits
     */
    public function __construct(private readonly array $hits)
    {
    }

    /**
     * @return array<Hit>
     */
    public function getHits(): array
    {
        return $this->hits;
    }
}
