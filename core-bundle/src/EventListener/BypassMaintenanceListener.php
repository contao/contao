<?php

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

/**
 * Adds the maintenance attribute to the request.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
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
     * Constructor.
     *
     * @param SessionInterface $session
     * @param bool             $disableIpCheck
     * @param string           $requestAttribute
     */
    public function __construct(SessionInterface $session, $disableIpCheck, $requestAttribute = '_bypass_maintenance')
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
    public function onKernelRequest(GetResponseEvent $event)
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
    private function hasAuthenticatedBackendUser(Request $request)
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
