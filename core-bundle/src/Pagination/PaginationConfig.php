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

use Symfony\Component\HttpFoundation\Request;

class PaginationConfig
{
    private Request|null $request = null;

    private int|null $pageRange = null;

    private bool $ignoreOutOfBounds = false;

    private int|null $currentPage = null;

    public function __construct(
        private readonly string $queryParameterName,
        private readonly int $total,
        private readonly int $perPage,
    ) {
    }

    /**
     * The request object the pagination should use to get the query parameter values from.
     */
    public function withRequest(Request $request): self
    {
        $clone = clone $this;
        $clone->request = $request;

        return $clone;
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

    /**
     * Forces the pagination to use the given page number as the current page.
     */
    public function withCurrentPage(int $currentPage): self
    {
        $clone = clone $this;
        $clone->currentPage = $currentPage;

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

    public function getRequest(): Request|null
    {
        return $this->request;
    }

    public function getPageRange(): int|null
    {
        return $this->pageRange;
    }

    public function getIgnoreOutOfBounds(): bool
    {
        return $this->ignoreOutOfBounds;
    }

    public function getCurrentPage(): int|null
    {
        return $this->currentPage;
    }
}
