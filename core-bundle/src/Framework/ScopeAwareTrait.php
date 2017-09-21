<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Event\KernelEvent;

@trigger_error('Using the Contao\CoreBundle\Framework\ScopeAwareTrait trait has been deprecated and will no longer work in Contao 5.0. Use the contao.routing.scope_matcher service instead.', E_USER_DEPRECATED);

/**
 * @deprecated Deprecated since Contao 4.4, to be removed in Contao 5; use the
 *             contao.routing.scope_matcher service instead
 */
trait ScopeAwareTrait
{
    use ContainerAwareTrait;

    /**
     * Checks whether the request is a Contao the master request.
     *
     * @param KernelEvent $event
     *
     * @return bool
     */
    protected function isContaoMasterRequest(KernelEvent $event): bool
    {
        return $event->isMasterRequest() && $this->isContaoScope();
    }

    /**
     * Checks whether the request is a Contao back end master request.
     *
     * @param KernelEvent $event
     *
     * @return bool
     */
    protected function isBackendMasterRequest(KernelEvent $event): bool
    {
        return $event->isMasterRequest() && $this->isBackendScope();
    }

    /**
     * Checks whether the request is a Contao front end master request.
     *
     * @param KernelEvent $event
     *
     * @return bool
     */
    protected function isFrontendMasterRequest(KernelEvent $event): bool
    {
        return $event->isMasterRequest() && $this->isFrontendScope();
    }

    /**
     * Checks whether the request is a Contao request.
     *
     * @return bool
     */
    protected function isContaoScope(): bool
    {
        return $this->isBackendScope() || $this->isFrontendScope();
    }

    /**
     * Checks whether the request is a Contao back end request.
     *
     * @return bool
     */
    protected function isBackendScope(): bool
    {
        return $this->isScope(ContaoCoreBundle::SCOPE_BACKEND);
    }

    /**
     * Checks whether the request is a Contao front end request.
     *
     * @return bool
     */
    protected function isFrontendScope(): bool
    {
        return $this->isScope(ContaoCoreBundle::SCOPE_FRONTEND);
    }

    /**
     * Checks whether the _scope attributes matches a scope.
     *
     * @param string $scope
     *
     * @return bool
     */
    private function isScope(string $scope): bool
    {
        if (null === $this->container
            || null === ($request = $this->container->get('request_stack')->getCurrentRequest())
        ) {
            return false;
        }

        $matcher = $this->container->get('contao.routing.scope_matcher');

        if (ContaoCoreBundle::SCOPE_BACKEND === $scope) {
            return $matcher->isBackendRequest($request);
        }

        if (ContaoCoreBundle::SCOPE_FRONTEND === $scope) {
            return $matcher->isFrontendRequest($request);
        }

        return false;
    }
}
