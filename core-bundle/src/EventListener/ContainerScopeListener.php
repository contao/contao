<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Changes the container scope based on the route configuration.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.0; use the _scope request attribute instead
 */
class ContainerScopeListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->addContaoScopesIfNotSet();
    }

    /**
     * Enters the container scope when a route has been found.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (null !== ($scope = $this->getScopeFromEvent($event))) {
            $this->container->enterScope($scope);
        }
    }

    /**
     * Leaves the container scope when finishing the request.
     *
     * @param FinishRequestEvent $event
     */
    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        if (null !== ($scope = $this->getScopeFromEvent($event))) {
            $this->container->leaveScope($scope);
        }
    }

    /**
     * Returns the scope from the event request.
     *
     * @param KernelEvent $event
     *
     * @return string|null
     */
    private function getScopeFromEvent(KernelEvent $event)
    {
        return $event->getRequest()->attributes->get('_scope');
    }

    /**
     * Adds the Contao scopes to the container.
     */
    private function addContaoScopesIfNotSet()
    {
        if (!$this->container->hasScope(ContaoCoreBundle::SCOPE_BACKEND)) {
            $this->container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND, 'request'));
        }

        if (!$this->container->hasScope(ContaoCoreBundle::SCOPE_FRONTEND)) {
            $this->container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND, 'request'));
        }
    }
}
