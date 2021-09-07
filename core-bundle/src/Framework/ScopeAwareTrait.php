<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Framework;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Event\KernelEvent;

trigger_deprecation('contao/core-bundle', '4.4', 'Using the "Contao\CoreBundle\Framework\ScopeAwareTrait" trait has been deprecated and will no longer work in Contao 5.0. Use the "contao.routing.scope_matcher" service instead.');

/**
 * @deprecated Deprecated since Contao 4.4, to be removed in Contao 5.0; use
 *             the contao.routing.scope_matcher service instead
 */
trait ScopeAwareTrait
{
    use ContainerAwareTrait;

    protected function isContaoMasterRequest(KernelEvent $event): bool
    {
        return $event->isMainRequest() && $this->isContaoScope();
    }

    protected function isBackendMasterRequest(KernelEvent $event): bool
    {
        return $event->isMainRequest() && $this->isBackendScope();
    }

    protected function isFrontendMasterRequest(KernelEvent $event): bool
    {
        return $event->isMainRequest() && $this->isFrontendScope();
    }

    protected function isContaoScope(): bool
    {
        return $this->isBackendScope() || $this->isFrontendScope();
    }

    protected function isBackendScope(): bool
    {
        return $this->isScope(ContaoCoreBundle::SCOPE_BACKEND);
    }

    protected function isFrontendScope(): bool
    {
        return $this->isScope(ContaoCoreBundle::SCOPE_FRONTEND);
    }

    /**
     * Checks whether the _scope attributes matches a scope.
     */
    private function isScope(string $scope): bool
    {
        if (
            null === $this->container
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
