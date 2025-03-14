<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DcaLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DcaRequestSwitcher
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @template T of mixed
     *
     * @param \Closure():T $callback
     *
     * @return T
     */
    public function runWithRequest(Request|string $request, \Closure $callback): mixed
    {
        if ($request === $this->requestStack->getCurrentRequest()) {
            return $callback();
        }

        $this->pushRequest($request);

        try {
            return $callback();
        } finally {
            $this->popRequest();
        }
    }

    public function pushRequest(Request|string $request): void
    {
        if (\is_string($request)) {
            $request = Request::create($request);
        }

        $this->requestStack->push($request);

        $this->framework->getAdapter(DcaLoader::class)->switchToCurrentRequest();
    }

    public function popRequest(): void
    {
        $this->requestStack->pop();

        $this->framework->getAdapter(DcaLoader::class)->switchToCurrentRequest();
    }
}
