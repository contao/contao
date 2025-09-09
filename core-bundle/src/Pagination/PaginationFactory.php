<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Pagination;

use Symfony\Component\HttpFoundation\RequestStack;

class PaginationFactory implements PaginationFactoryInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly int|null $pageRange = 7,
    ) {
    }

    public function create(string $param, int $total, int $perPage, int|null $pageRange = null, bool $throw = true): PaginationInterface
    {
        return new Pagination(
            $this->requestStack->getCurrentRequest(),
            $param,
            $total,
            $perPage,
            $pageRange ?? $this->pageRange,
            $throw,
        );
    }
}
