<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Pagination;

use Symfony\Component\HttpFoundation\RequestStack;

class PaginationFactory implements PaginationFactoryInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly int|null $defaultRange = 7,
    ) {
    }

    public function create(PaginationConfig $config): PaginationInterface
    {
        // Set the default page range if not defined
        if (null === $config->getPageRange() && null !== $this->defaultRange) {
            $config = $config->withPageRange($this->defaultRange);
        }

        return new Pagination($this->requestStack->getCurrentRequest(), $config);
    }
}
