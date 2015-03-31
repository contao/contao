<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Changes the container scope based on the route configuration.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
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
     * @param KernelEvent $event The event object
     *
     * @return string|null The scope name
     */
    private function getScopeFromEvent(KernelEvent $event)
    {
        if (!$event->getRequest()->attributes->has('_scope')) {
            return null;
        }

        $scope = $event->getRequest()->attributes->get('_scope');

        if (!$this->container->hasScope($scope)) {
            return null;
        }

        return $scope;
    }
}
