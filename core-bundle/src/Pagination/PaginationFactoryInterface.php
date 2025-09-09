<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Pagination;

interface PaginationFactoryInterface
{
    public function create(string $param, int $total, int $perPage, int|null $pageRange = null): PaginationInterface;
}
