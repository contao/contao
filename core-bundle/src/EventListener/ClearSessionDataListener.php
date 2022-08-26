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

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 */
class ClearSessionDataListener
{
    /**
     * Clear the Contao session data if not a POST request.
     */
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->isMethod('POST')) {
            return;
        }

        if (!$request->hasSession() || !$request->getSession()->isStarted()) {
            return;
        }

        $session = $request->getSession();

        if ($event->getResponse()->isSuccessful()) {
            $this->clearLoginData($session);
        }

        $session->remove('CURRENT_ID');

        $this->clearLegacyAttributeBags('FE_DATA');
        $this->clearLegacyAttributeBags('BE_DATA');
        $this->clearLegacyFormData();
    }

    private function clearLegacyAttributeBags(string $key): void
    {
        if (!isset($_SESSION[$key])) {
            return;
        }

        if (($bag = $_SESSION[$key]) instanceof AttributeBag && !$bag->count()) {
            unset($_SESSION[$key]);
        }
    }

    private function clearLegacyFormData(): void
    {
        if (isset($_SESSION['FORM_DATA']['SUBMITTED_AT'])) {
            $waitingTime = max(30, (int) \ini_get('max_execution_time')) * 2;

            // Leave the data available for $waitingTime seconds (for redirect confirmation pages)
            if ($_SESSION['FORM_DATA']['SUBMITTED_AT'] + $waitingTime > time()) {
                return;
            }
        }

        unset($_SESSION['FORM_DATA'], $_SESSION['FILES']);
    }

    private function clearLoginData(SessionInterface $session): void
    {
        $session->remove(Security::AUTHENTICATION_ERROR);
        $session->remove(Security::LAST_USERNAME);
    }
}
