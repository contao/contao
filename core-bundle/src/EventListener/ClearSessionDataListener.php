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
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class ClearSessionDataListener
{
    /**
     * Clear the Contao form data if not a POST request.
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

        $this->clearLegacyAttributeBags('FE_DATA');
        $this->clearLegacyAttributeBags('BE_DATA');
        $this->clearLegacyFormData();
    }

    private function clearLegacyFormData()
    {
        if (!isset($_SESSION['FORM_DATA']['SUBMITTED_AT'])) {
            unset($_SESSION['FORM_DATA']);
            return;
        }

        // Leave the data available for 10 secods (for redirect confirmation pages)
        if (((int) $_SESSION['FORM_DATA']['SUBMITTED_AT'] + 10) > time()) {
            return;
        }

        unset($_SESSION['FORM_DATA']);
        unset($_SESSION['FILES']);
    }

    private function clearLegacyAttributeBags(string $key)
    {
        if (isset($_SESSION[$key]) && $_SESSION[$key] instanceof AttributeBagInterface) {
            if (!$_SESSION[$key]->count()) {
                unset($_SESSION[$key]);
            }
        }
    }
}
