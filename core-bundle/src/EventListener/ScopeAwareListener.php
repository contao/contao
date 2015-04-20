<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Provides methods to test the container scope.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
abstract class ScopeAwareListener extends ContainerAware
{
    /**
     * Checks whether the request is the master request in one of the Contao scopes.
     *
     * @param KernelEvent $event The HttpKernel event
     *
     * @return bool True the request is the master request in one of the Contao scopes
     */
    protected function isContaoMasterRequest(KernelEvent $event)
    {
        return $event->isMasterRequest() && $this->isContaoScope();
    }

    /**
     * Checks whether the request is the master request in the back end scope.
     *
     * @param KernelEvent $event The HttpKernel event
     *
     * @return bool True the request is the master request in the back end scope
     */
    protected function isBackendMasterRequest(KernelEvent $event)
    {
        return $event->isMasterRequest() && $this->isBackendScope();
    }

    /**
     * Checks whether the request is the master request in the front end scope.
     *
     * @param KernelEvent $event The HttpKernel event
     *
     * @return bool True if the request is the master request in the front end scope
     */
    protected function isFrontendMasterRequest(KernelEvent $event)
    {
        return $event->isMasterRequest() && $this->isFrontendScope();
    }

    /**
     * Checks whether the container is in one of the Contao scopes.
     *
     * @return bool True if the container is in one of the Contao scopes
     */
    protected function isContaoScope()
    {
        return $this->isBackendScope() || $this->isFrontendScope();
    }

    /**
     * Checks whether the container is in the back end scope.
     *
     * @return bool True if the container is in the back end scope
     */
    protected function isBackendScope()
    {
        return (null !== $this->container && $this->container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND));
    }

    /**
     * Checks whether the container is in the front end scope.
     *
     * @return bool True if the container is in the front end scope
     */
    protected function isFrontendScope()
    {
        return (null !== $this->container && $this->container->isScopeActive(ContaoCoreBundle::SCOPE_FRONTEND));
    }
}
