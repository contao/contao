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
use Symfony\Component\HttpKernel\Event\KernelEvent;

class ScopeMatcher
{
    /**
     * @var RequestMatcherInterface
     */
    private $backendMatcher;

    /**
     * @var RequestMatcherInterface
     */
    private $frontendMatcher;

    /**
     * @internal Do not inherit from this class; decorate the "contao.routing.scope_matcher" service instead
     */
    public function __construct(RequestMatcherInterface $backendMatcher, RequestMatcherInterface $frontendMatcher)
    {
        $this->backendMatcher = $backendMatcher;
        $this->frontendMatcher = $frontendMatcher;
    }

    public function isContaoMasterRequest(KernelEvent $event): bool
    {
        return $event->isMasterRequest() && $this->isContaoRequest($event->getRequest());
    }

    public function isBackendMasterRequest(KernelEvent $event): bool
    {
        return $event->isMasterRequest() && $this->isBackendRequest($event->getRequest());
    }

    public function isFrontendMasterRequest(KernelEvent $event): bool
    {
        return $event->isMasterRequest() && $this->isFrontendRequest($event->getRequest());
    }

    public function isContaoRequest(Request $request): bool
    {
        return $this->isBackendRequest($request) || $this->isFrontendRequest($request);
    }

    public function isBackendRequest(Request $request): bool
    {
        return $this->backendMatcher->matches($request);
    }

    public function isFrontendRequest(Request $request): bool
    {
        return $this->frontendMatcher->matches($request);
    }
}
