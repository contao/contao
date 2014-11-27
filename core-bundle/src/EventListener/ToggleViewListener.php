<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Sets TL_VIEW cookie based on query parameter "toggle_view"
 *
 * @author Leo Feyer <https://contao.org>
 * @author Andreas Schempp <http://terminal42.ch>
 */
class ToggleViewListener
{
    /**
     * Toggles the TL_VIEW cookie and redirect back to referrer
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->query->has('toggle_view')) {
            $state = (string) $request->query->get('toggle_view');
            $referer = \System::getReferer();
            $response = new RedirectResponse($referer, 303);

            if ('mobile' === $state) {
                $cookie = new Cookie('TL_VIEW', 'mobile', 0);
            } else {
                $cookie = new Cookie('TL_VIEW', 'desktop', 0);
            }

            $response->headers->setCookie($cookie);
            $event->setResponse($response);
        }
    }
}
