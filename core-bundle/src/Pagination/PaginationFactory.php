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
        // Set the default request object if not defined
        if (!$config->getRequest() && ($request = $this->requestStack->getCurrentRequest())) {
            $config = $config->withRequest($request);
        }

        // Set the default page range if not defined
        if (null === $config->getPageRange() && null !== $this->defaultRange) {
            $config = $config->withPageRange($this->defaultRange);
        }

        return new Pagination($config);
    }
}
