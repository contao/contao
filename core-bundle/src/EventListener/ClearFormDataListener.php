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

class ClearFormDataListener
{
    /**
     * Clear the Contao form data if not a POST request.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->isMethod('POST')) {
            return;
        }

        if (null === ($session = $request->getSession()) || !$session->isStarted()) {
            return;
        }

        unset($_SESSION['FORM_DATA']);
    }
}
