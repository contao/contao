<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Outputs a page from cache without loading a controller.
 *
 * @author Leo Feyer <https://contao.org>
 * @author Andreas Schempp <http://terminal42.ch>
 *
 * @codeCoverageIgnore
 */
class OutputFromCacheListener
{
    /**
     * Forwards the request to the Frontend class and sets the response if any.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $response = \Frontend::getResponseFromCache();

        if (null !== $response) {
            $event->setResponse($response);
        }
    }
}
