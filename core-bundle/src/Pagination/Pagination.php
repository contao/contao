<?php

declare(strict_types=1);

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

    private readonly int $currentPage;

    public function __construct(
        private readonly Request $request,
        private readonly string $param,
        private readonly int $total,
        private readonly int $perPage,
        private int|null $pageRange = null,
        bool $throw = true,
    ) {
        $this->pageCount = (int) ceil($total / $perPage);

        $this->currentPage = $this->request->query->getInt($this->getParam(), 1);

        if ($this->currentPage < 1 || $this->currentPage > $this->pageCount) {
            if ($throw) {
                throw new PageOutOfRangeException(\sprintf('Page %s is out of range.', $this->currentPage));
            }

            $this->currentPage = max(1, min($this->currentPage, $this->pageCount));
        }

        if (null === $pageRange || $pageRange > $this->pageCount) {
            $this->pageRange = $this->pageCount;
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
        return $this->perPage;
    }

    public function getCurrent(): int
    {
        return $this->currentPage;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function getFirst(): int|null
    {
        return $this->getCurrent() > 1 ? 1 : null;
    }

    public function getPrevious(): int|null
    {
        $previousPage = $this->getCurrent() - 1;

        return $previousPage >= 1 ? $previousPage : null;
    }

    public function getLast(): int|null
    {
        $lastPage = $this->getPageCount();

        if ($this->getCurrent() === $lastPage) {
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
        $params[$this->getParam()] = $page;

        return (string) (new Uri($this->request->getRequestUri()))->withQuery(http_build_query($params));
    }

    public function getParam(): string
    {
        return $this->param;
    }

    public function getItemsForPage(array $items, int|null $page = null): array
    {
        $offset = (($page ?? $this->getCurrent()) - 1) * $this->getPerPage();

        return \array_slice($items, $offset, $this->perPage);
    }
}
