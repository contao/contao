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

interface PaginationInterface
{
    /**
     * @return list<int>
     */
    public function getPages(): array;

    public function getPerPage(): int;

    public function getCurrent(): int;

    public function getTotal(): int;

    public function getPageCount(): int;

    public function getFirst(): int|null;

    public function getPrevious(): int|null;

    public function getLast(): int|null;

    public function getNext(): int|null;

    public function getUrlForPage(int $page): string;

    public function getQueryParameterName(): string;

    public function getItemsForPage(array $items, int|null $page = null): array;

    public function getOffset(): int;

    /**
     * @return list<int>
     */
    public function getIndexRange(): array;
}
