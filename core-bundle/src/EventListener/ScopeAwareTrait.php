<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Provides methods to test the request scope.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
 *             Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.
 */
trait ScopeAwareTrait
{
    use ContainerAwareTrait;

    /**
     * Checks whether the request is a Contao the master request.
     *
     * @param KernelEvent $event The HttpKernel event
     *
     * @return bool True the request is a Contao the master request
     */
    protected function isContaoMasterRequest(KernelEvent $event)
    {
        @trigger_error(
            'Using Contao\CoreBundle\EventListener\ScopeAwareTrait has been deprecated and will no longer work in '
                . 'Contao 5.0. Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.',
            E_USER_DEPRECATED
        );

        return $event->isMasterRequest() && $this->isContaoScope();
    }

    /**
     * Checks whether the request is a Contao back end master request.
     *
     * @param KernelEvent $event The HttpKernel event
     *
     * @return bool True the request is a Contao back end master request
     */
    protected function isBackendMasterRequest(KernelEvent $event)
    {
        @trigger_error(
            'Using Contao\CoreBundle\EventListener\ScopeAwareTrait has been deprecated and will no longer work in '
                . 'Contao 5.0. Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.',
            E_USER_DEPRECATED
        );

        return $event->isMasterRequest() && $this->isBackendScope();
    }

    /**
     * Checks whether the request is a Contao front end master request.
     *
     * @param KernelEvent $event The HttpKernel event
     *
     * @return bool True if the request is a Contao front end master request
     */
    protected function isFrontendMasterRequest(KernelEvent $event)
    {
        @trigger_error(
            'Using Contao\CoreBundle\EventListener\ScopeAwareTrait has been deprecated and will no longer work in '
                . 'Contao 5.0. Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.',
            E_USER_DEPRECATED
        );

        return $event->isMasterRequest() && $this->isFrontendScope();
    }

    /**
     * Checks whether the request is a Contao request.
     *
     * @return bool True if the request is a Contao request
     */
    protected function isContaoScope()
    {
        @trigger_error(
            'Using Contao\CoreBundle\EventListener\ScopeAwareTrait has been deprecated and will no longer work in '
                . 'Contao 5.0. Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.',
            E_USER_DEPRECATED
        );

        return $this->isBackendScope() || $this->isFrontendScope();
    }

    /**
     * Checks whether the request is a Contao back end request.
     *
     * @return bool True if the request is a Contao back end request
     */
    protected function isBackendScope()
    {
        @trigger_error(
            'Using Contao\CoreBundle\EventListener\ScopeAwareTrait has been deprecated and will no longer work in '
                . 'Contao 5.0. Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.',
            E_USER_DEPRECATED
        );

        return $this->isScope(ContaoCoreBundle::SCOPE_BACKEND);
    }

    /**
     * Checks whether the request is a Contao front end request.
     *
     * @return bool True if the request is a Contao front end request
     */
    protected function isFrontendScope()
    {
        @trigger_error(
            'Using Contao\CoreBundle\EventListener\ScopeAwareTrait has been deprecated and will no longer work in '
                . 'Contao 5.0. Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.',
            E_USER_DEPRECATED
        );

        return $this->isScope(ContaoCoreBundle::SCOPE_FRONTEND);
    }

    /**
     * Checks whether the _scope attributes matches a scope.
     *
     * @param string $scope The scope
     *
     * @return bool True if the _scope attributes matches a scope
     */
    private function isScope($scope)
    {
        @trigger_error(
            'Using Contao\CoreBundle\EventListener\ScopeAwareTrait has been deprecated and will no longer work in '
                . 'Contao 5.0. Use Contao\CoreBundle\Framework\ScopeAwareTrait instead.',
            E_USER_DEPRECATED
        );

        if (null === $this->container) {
            return false;
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();

        if (null === $request && !$request->attributes->has('_scope')) {
            return false;
        }

        return $scope === $request->attributes->get('_scope');
    }
}
