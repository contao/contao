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

/**
 * Changes the container scope based on the route configuration.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContainerScopeListener
{
    // FIXME: add tests
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
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$event->getRequest()->attributes->has('_scope')) {
            return;
        }

        $scope = $event->getRequest()->attributes->get('_scope');

        if ($this->container->hasScope($scope)) {
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
        if (!$event->getRequest()->attributes->has('_scope')) {
            return;
        }

        $scope = $event->getRequest()->attributes->get('_scope');

        if ($this->container->hasScope($scope)) {
            $this->container->leaveScope($scope);
        }
    }
}
