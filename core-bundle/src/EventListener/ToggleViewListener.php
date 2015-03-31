<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\System;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Sets the TL_VIEW cookie based on the "toggle_view" query parameter.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ToggleViewListener extends ScopeAwareListener
{
    /**
     * Toggles the TL_VIEW cookie and redirects back to the referring page.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->isFrontendMasterRequest($event)
            || !$request->query->has('toggle_view')
        ) {
            return;
        }

        $referer  = System::getReferer();
        $response = new RedirectResponse($referer, 303);

        $response->headers->setCookie(
            $this->getCookie($request->query->get('toggle_view'), $request->getBasePath())
        );

        $event->setResponse($response);
    }

    /**
     * Generates the TL_VIEW cookie based on the toggle_view value.
     *
     * @param string $state    The cookie state ("desktop" or "mobile")
     * @param string $basePath The request base path
     *
     * @return Cookie
     */
    private function getCookie($state, $basePath)
    {
        if ('mobile' !== $state) {
            $state = 'desktop';
        }

        return new Cookie('TL_VIEW', $state, 0, $basePath);
    }
}
