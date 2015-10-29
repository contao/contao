<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Sets route parameter to bypass maintenance mode if a backend user is logged in.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
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
    private $checkIp;

    /**
     * @var string
     */
    private $requestAttribute;

    /**
     * Constructor.
     *
     * @param SessionInterface $session
     * @param bool             $checkIp
     * @param string           $requestAttribute
     */
    public function __construct(SessionInterface $session, $checkIp, $requestAttribute = '_bypass_maintenance')
    {
        $this->session          = $session;
        $this->checkIp          = $checkIp;
        $this->requestAttribute = $requestAttribute;
    }

    /**
     * Sets request attribute to disable maintenance mode if backend user is logged in.
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $request->attributes->set($this->requestAttribute, $this->hasValidSessionCookie($request));
    }

    /**
     * Validates session cookie in the given request.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function hasValidSessionCookie(Request $request)
    {
        if ($request->cookies->has('BE_USER_AUTH')) {
            return false;
        }

        $sessionHash = sha1(
            sprintf(
                '%s%sBE_USER_AUTH',
                $this->session->getId(),
                ($this->checkIp ? $request->getClientIp() : '')
            )
        );

        return $request->cookies->get('BE_USER_AUTH') === $sessionHash;
    }
}
