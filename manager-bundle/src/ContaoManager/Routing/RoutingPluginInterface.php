<?php

namespace Contao\ManagerBundle\ContaoManager\Routing;

use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

interface RoutingPluginInterface
{
    /**
     * Returns a collection of routes for this bundle.
     *
     * @param LoaderResolverInterface $resolver
     * @param KernelInterface         $kernel
     *
     * @return null|RouteCollection
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel);
}
