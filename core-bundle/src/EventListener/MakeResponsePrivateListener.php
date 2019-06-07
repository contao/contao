<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class MakeResponsePrivateListener
{
    /**
     * Make sure that the current response becomes a private response if any
     * of the following conditions are true.
     *
     *   1. The session was started
     *   2. The response sets a cookie (same reason as 1 but for other cookies than the session cookie)
     *   3. The response has a "Vary: Cookie" header and the request provides at least one cookie
     *
     * Some of this logic is also already implemented in the HttpCache (1 and 2), but we
     * want to make sure it works for any reverse proxy without having to configure too much.
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // If the response is not cacheable for a reverse proxy, we don't have to do anything anyway
        if (!$response->isCacheable()) {
            return;
        }

        // 1) The session was started
        if (null !== ($session = $request->getSession()) && $session->isStarted()) {
            $response->setPrivate();

            return;
        }

        // 2) The response sets a cookie (same reason as 1 but for other cookies than the session cookie)
        if (0 !== \count($response->headers->getCookies())) {
            $response->setPrivate();

            return;
        }

        // 3) The response has a "Vary: Cookie" header and the request provides at least one cookie
        if ($request->cookies->count() && \in_array('cookie', array_map('strtolower', $response->getVary()), true)) {
            $response->setPrivate();
        }
    }
}
