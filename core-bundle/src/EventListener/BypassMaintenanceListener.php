<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
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
     * @var RequestStack
     */
    private $requestStack;

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
     * @param RequestStack     $requestStack
     * @param bool             $disableIpCheck
     * @param string           $requestAttribute
     */
    public function __construct(SessionInterface $session, RequestStack $requestStack, $disableIpCheck, $requestAttribute = '_bypass_maintenance')
    {
        $this->session = $session;
        $this->requestStack = $requestStack;
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

        if (!$this->hasAuthenticatedBackendUser()) {
            return;
        }

        $request->attributes->set($this->requestAttribute, true);
    }

    /**
     * Checks if there is an authenticated back end user.
     *
     * @return bool
     */
    private function hasAuthenticatedBackendUser()
    {
        $request = $this->requestStack->getMasterRequest();

        if (null === $request || !$request->cookies->has('BE_USER_AUTH')) {
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
