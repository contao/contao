<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Frontend;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Outputs a page from cache without loading a controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class OutputFromCacheListener extends ContainerAware
{
    /**
     * Forwards the request to the Frontend class and sets the response if any.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()
            || null === $this->container
            || !$this->container->isScopeActive('frontend')
        ) {
            return;
        }

        $response = Frontend::getResponseFromCache();

        if (null !== $response) {
            $event->setResponse($response);
        }
    }
}
