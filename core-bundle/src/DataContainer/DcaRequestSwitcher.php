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
use Symfony\Contracts\Service\ResetInterface;

class DcaRequestSwitcher implements ResetInterface
{
    /**
     * @var array<string, Request>
     */
    private array $requestCache = [];

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
    public function runWithRequest(Request|string|null $request, \Closure $callback): mixed
    {
        $request ??= $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \LogicException('Unable to retrieve DCA information from empty request stack.');
        }

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
            if ($this->requestCache[$request] ?? null) {
                $request = $this->requestCache[$request];
            } else {
                $request = $this->requestCache[$request] = Request::create($request);

                // Copy the session as the security voters need it
                if ($session = $this->requestStack->getCurrentRequest()?->getSession()) {
                    $request->setSession($session);
                }
            }
        }

        $this->requestStack->push($request);

        $this->framework->getAdapter(DcaLoader::class)->switchToCurrentRequest();
    }

    public function popRequest(): void
    {
        $this->requestStack->pop();

        $this->framework->getAdapter(DcaLoader::class)->switchToCurrentRequest();
    }

    public function reset(): void
    {
        $this->requestCache = [];
    }
}
