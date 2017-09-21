<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class BypassMaintenanceListener
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var bool
     */
    private $disableIpCheck;

    /**
     * @var string
     */
    private $requestAttribute;

    /**
     * @param SessionInterface $session
     * @param bool             $disableIpCheck
     * @param string           $requestAttribute
     */
    public function __construct(SessionInterface $session, bool $disableIpCheck, string $requestAttribute = '_bypass_maintenance')
    {
        $this->session = $session;
        $this->disableIpCheck = $disableIpCheck;
        $this->requestAttribute = $requestAttribute;
    }

    /**
     * Adds the request attribute to the request.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->hasAuthenticatedBackendUser($request)) {
            return;
        }

        $request->attributes->set($this->requestAttribute, true);
    }

    /**
     * Checks if there is an authenticated back end user.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function hasAuthenticatedBackendUser(Request $request): bool
    {
        if (!$request->cookies->has('BE_USER_AUTH')) {
            return false;
        }

        $sessionHash = sha1(
            sprintf(
                '%s%sBE_USER_AUTH',
                $this->session->getId(),
                $this->disableIpCheck ? '' : $request->getClientIp()
            )
        );

        return $request->cookies->get('BE_USER_AUTH') === $sessionHash;
    }
}
