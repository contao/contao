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
 * Change the container scope based on the current route configuration.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContainerScopeListener
{
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
     * Enter the container scope when a route has been found.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $scope = $event->getRequest()->attributes->get('_scope');

        if ($scope && $this->container->hasScope($scope)) {
            $this->container->enterScope($scope);
        }
    }


    /**
     * Leave the container scope when finishing the request
     *
     * @param FinishRequestEvent $event
     */
    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        $scope = $event->getRequest()->attributes->get('_scope');

        if ($scope && $this->container->hasScope($scope)) {
            $this->container->leaveScope($scope);
        }
    }
}
