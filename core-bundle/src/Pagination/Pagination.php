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

use Contao\CoreBundle\Exception\PageOutOfRangeException;
use Nyholm\Psr7\Uri;
use Symfony\Component\HttpFoundation\Request;

class Pagination implements PaginationInterface
{
    /**
     * @var list<int>
     */
    private readonly array $pages;

    private readonly int $pageCount;

    private readonly int $pageRange;

    private int $currentPage;

    public function __construct(
        private readonly Request $request,
        private readonly PaginationConfig $config,
    ) {
        $this->pageCount = $config->getPerPage() > 0 ? (int) ceil($config->getTotal() / $config->getPerPage()) : 0;

        $this->currentPage = $this->request->query->getInt($this->getQueryParameterName(), 1);

        if ($this->currentPage < 1 || $this->currentPage > $this->pageCount) {
            if (!$config->getIgnoreOutOfBounds()) {
                throw new PageOutOfRangeException(\sprintf('Page %s is out of range.', $this->currentPage));
            }

            $this->currentPage = max(1, min($this->currentPage, $this->pageCount));
        }

        if (!$config->getPageRange() || $config->getPageRange() > $this->pageCount) {
            $this->pageRange = $this->pageCount;
        } else {
            $this->pageRange = (int) $config->getPageRange();
        }

        $delta = ceil($this->pageRange / 2);

        if ($this->currentPage - $delta > $this->pageCount - $this->pageRange) {
            $this->pages = range($this->pageCount - $this->pageRange + 1, $this->pageCount);
        } else {
            if ($this->currentPage - $delta < 0) {
                $delta = $this->currentPage;
            }

            $offset = $this->currentPage - $delta;
            $this->pages = range($offset + 1, $offset + $this->pageRange);
        }
    }

    public function getPages(): array
    {
        return $this->pages;
    }

    public function getPerPage(): int
    {
        return $this->config->getPerPage();
    }

    public function getCurrent(): int
    {
        return $this->currentPage;
    }

    public function getTotal(): int
    {
        return $this->config->getTotal();
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function getFirst(): int|null
    {
        return $this->getCurrent() > 2 ? 1 : null;
    }

    public function getPrevious(): int|null
    {
        $previousPage = $this->getCurrent() - 1;

        return $previousPage >= 1 ? $previousPage : null;
    }

    public function getLast(): int|null
    {
        $lastPage = $this->getPageCount();

        if ($this->getCurrent() >= $lastPage - 1) {
            return null;
        }

        return $lastPage;
    }

    public function getNext(): int|null
    {
        $nextPage = $this->getCurrent() + 1;

        return $nextPage <= $this->getPageCount() ? $nextPage : null;
    }

    public function getUrlForPage(int $page): string
    {
        $params = $this->request->query->all();
        $params[$this->getQueryParameterName()] = $page;

        return (string) (new Uri($this->request->getRequestUri()))->withQuery(http_build_query($params));
    }

    public function getQueryParameterName(): string
    {
        return $this->config->getQueryParameterName();
    }

    public function getItemsForPage(array $items, int|null $page = null): array
    {
        $offset = (($page ?? $this->getCurrent()) - 1) * $this->getPerPage();

        return \array_slice($items, $offset, $this->getPerPage());
    }

    public function getOffset(): int
    {
        return ($this->getCurrent() - 1) * $this->getPerPage();
    }

    public function getIndexRange(): array
    {
        $start = $this->getOffset();
        $end = min($this->getPerPage() + $start, $this->getTotal());

        return [$start, $end];
    }
}
