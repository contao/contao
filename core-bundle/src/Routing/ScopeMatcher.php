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
    private RequestMatcherInterface $backendMatcher;
    private RequestMatcherInterface $frontendMatcher;

    /**
     * @internal
     */
    public function __construct(RequestMatcherInterface $backendMatcher, RequestMatcherInterface $frontendMatcher)
    {
        $this->backendMatcher = $backendMatcher;
        $this->frontendMatcher = $frontendMatcher;
    }

    public function isContaoMainRequest(KernelEvent $event): bool
    {
        return $event->isMainRequest() && $this->isContaoRequest($event->getRequest());
    }

    /**
     * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0; use
     *             ScopeMatcher::isContaoMainRequest() instead
     */
    public function isContaoMasterRequest(KernelEvent $event): bool
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using ScopeMatcher::isContaoMasterRequest() has been deprecated and will no longer work in Contao 5.0. Use ScopeMatcher::isContaoMainRequest() instead.');

        return $this->isContaoMainRequest($event);
    }

    public function isBackendMainRequest(KernelEvent $event): bool
    {
        return $event->isMainRequest() && $this->isBackendRequest($event->getRequest());
    }

    /**
     * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0; use
     *             ScopeMatcher::isBackendMainRequest() instead
     */
    public function isBackendMasterRequest(KernelEvent $event): bool
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using ScopeMatcher::isBackendMasterRequest() has been deprecated and will no longer work in Contao 5.0. Use ScopeMatcher::isBackendMainRequest() instead.');

        return $this->isBackendMainRequest($event);
    }

    public function isFrontendMainRequest(KernelEvent $event): bool
    {
        return $event->isMainRequest() && $this->isFrontendRequest($event->getRequest());
    }

    /**
     * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0; use
     *             ScopeMatcher::isFrontendMainRequest() instead
     */
    public function isFrontendMasterRequest(KernelEvent $event): bool
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using ScopeMatcher::isFrontendMasterRequest() has been deprecated and will no longer work in Contao 5.0. Use ScopeMatcher::isFrontendMainRequest() instead.');

        return $this->isFrontendMainRequest($event);
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
