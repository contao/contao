<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Pagination;

class PaginationConfig
{
    private int|null $pageRange = null;

    private bool $ignoreOutOfBounds = false;

    public function __construct(
        private string $queryParameterName,
        private int $total,
        private int $perPage,
    ) {
    }

    /**
     * Configures the number of pagination links to be shown, centered around the
     * current page index.
     */
    public function withPageRange(int $pageRange): self
    {
        $clone = clone $this;
        $clone->pageRange = $pageRange;

        return $clone;
    }

    /**
     * Tells the pagination to not throw an exception if the current page is out of
     * bounds and clamp the current page index to the last page instead.
     */
    public function withIgnoreOutOfBounds(bool $ignoreOutOfBounds = true): self
    {
        $clone = clone $this;
        $clone->ignoreOutOfBounds = $ignoreOutOfBounds;

        return $clone;
    }

    public function getQueryParameterName(): string
    {
        return $this->queryParameterName;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getPageRange(): int|null
    {
        return $this->pageRange;
    }

    public function getIgnoreOutOfBounds(): bool
    {
        return $this->ignoreOutOfBounds;
    }
}
