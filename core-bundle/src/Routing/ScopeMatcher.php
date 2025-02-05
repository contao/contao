<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\KernelEvent;

class ScopeMatcher
{
    /**
     * @internal
     */
    public function __construct(
        private readonly RequestMatcherInterface $backendMatcher,
        private readonly RequestMatcherInterface $frontendMatcher,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function isContaoMainRequest(KernelEvent $event): bool
    {
        return $event->isMainRequest() && $this->isContaoRequest($event->getRequest());
    }

    public function isBackendMainRequest(KernelEvent $event): bool
    {
        return $event->isMainRequest() && $this->isBackendRequest($event->getRequest());
    }

    public function isFrontendMainRequest(KernelEvent $event): bool
    {
        return $event->isMainRequest() && $this->isFrontendRequest($event->getRequest());
    }

    public function isContaoRequest(Request|null $request = null): bool
    {
        $request ??= $this->requestStack->getCurrentRequest();

        return $this->isBackendRequest($request) || $this->isFrontendRequest($request);
    }

    public function isBackendRequest(Request|null $request = null): bool
    {
        return $this->backendMatcher->matches($request ?? $this->requestStack->getCurrentRequest());
    }

    public function isFrontendRequest(Request|null $request = null): bool
    {
        return $this->frontendMatcher->matches($request ?? $this->requestStack->getCurrentRequest());
    }
}
