<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Checks the request for a Contao scope.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
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
     * Constructor.
     *
     * @param RequestMatcherInterface $backendMatcher
     * @param RequestMatcherInterface $frontendMatcher
     */
    public function __construct(RequestMatcherInterface $backendMatcher, RequestMatcherInterface $frontendMatcher)
    {
        $this->backendMatcher = $backendMatcher;
        $this->frontendMatcher = $frontendMatcher;
    }

    /**
     * Checks whether the request is a Contao master request.
     *
     * @param KernelEvent $event
     *
     * @return bool
     */
    public function isContaoMasterRequest(KernelEvent $event)
    {
        return $event->isMasterRequest() && $this->isContaoRequest($event->getRequest());
    }

    /**
     * Checks whether the request is a Contao back end master request.
     *
     * @param KernelEvent $event
     *
     * @return bool
     */
    public function isBackendMasterRequest(KernelEvent $event)
    {
        return $event->isMasterRequest() && $this->isBackendRequest($event->getRequest());
    }

    /**
     * Checks whether the request is a Contao front end master request.
     *
     * @param KernelEvent $event
     *
     * @return bool
     */
    public function isFrontendMasterRequest(KernelEvent $event)
    {
        return $event->isMasterRequest() && $this->isFrontendRequest($event->getRequest());
    }

    /**
     * Checks whether the request is a Contao request.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function isContaoRequest(Request $request)
    {
        return $this->isBackendRequest($request) || $this->isFrontendRequest($request);
    }

    /**
     * Checks whether the request is a Contao back end request.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function isBackendRequest(Request $request)
    {
        return $this->backendMatcher->matches($request);
    }

    /**
     * Checks whether the request is a Contao front end request.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function isFrontendRequest(Request $request)
    {
        return $this->frontendMatcher->matches($request);
    }
}
