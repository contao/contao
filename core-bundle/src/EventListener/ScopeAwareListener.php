<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

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
     * Returns wether this is the master request in frontend scope.
     *
     * @param KernelEvent $event The HttpKernel event
     *
     * @return bool
     */
    protected function isFrontendMasterRequest(KernelEvent $event)
    {
        return $event->isMasterRequest() && $this->isFrontendScope();
    }

    /**
     * Returns wether the container is in frontend scope.
     *
     * @return bool
     */
    protected function isFrontendScope()
    {
        return (null !== $this->container && $this->container->isScopeActive('frontend'));
    }

    /**
     * Returns wether the container is in backend scope.
     *
     * @return bool
     */
    protected function isBackendScope()
    {
        return (null !== $this->container && $this->container->isScopeActive('backend'));
    }
}
