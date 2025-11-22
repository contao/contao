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
     * @param array<Hit>   $hits
     * @param array<Facet> $typeFacets
     * @param array<Facet> $tagFacets
     */
    public function __construct(
        private readonly array $hits,
        private readonly array $typeFacets = [],
        private readonly array $tagFacets = [],
    ) {
    }

    /**
     * @return array<Hit>
     */
    public function getHits(): array
    {
        return $this->hits;
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @return array<Facet>
     */
    public function getTypeFacets(): array
    {
        return $this->typeFacets;
    }

    /**
     * @return array<Facet>
     */
    public function getTagFacets(): array
    {
        return $this->tagFacets;
    }
}
