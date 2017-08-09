<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener\HeaderReplay;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\HeaderReplay\Event\HeaderReplayEvent;
use Terminal42\HeaderReplay\EventListener\HeaderReplayListener;

/**
 * Disables the reverse proxy based on the terminal42/header-replay-bundle.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class UserSessionListener
{
    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var bool
     */
    private $disableIpCheck;

    /**
     * Constructor.
     *
     * @param ScopeMatcher $scopeMatcher
     * @param bool         $disableIpCheck
     */
    public function __construct(ScopeMatcher $scopeMatcher, $disableIpCheck)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->disableIpCheck = $disableIpCheck;
    }

    /**
     * Sets the "force no cache" header on the replay response to disable reverse proxy
     * caching if a back end user is logged in (front end preview mode).
     *
     * @param HeaderReplayEvent $event
     */
    public function onReplay(HeaderReplayEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->scopeMatcher->isContaoRequest($request) || !$this->hasContaoUser($request)) {
            return;
        }

        $event->getHeaders()->set(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME, 'true');
    }

    /**
     * Checks if there is a Contao user.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function hasContaoUser(Request $request)
    {
        if (!$request->hasSession()) {
            return false;
        }

        return $this->hasFrontendUser($request) || $this->hasBackendUser($request);
    }

    /**
     * Checks if there is a front end user.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function hasFrontendUser(Request $request)
    {
        if (!$request->cookies->has('FE_USER_AUTH')) {
            return false;
        }

        $sessionHash = sha1(
            sprintf(
                '%s%sFE_USER_AUTH',
                $request->getSession()->getId(),
                $this->disableIpCheck ? '' : $request->getClientIp()
            )
        );

        return $request->cookies->get('FE_USER_AUTH') === $sessionHash;
    }

    /**
     * Checks if there is a back end user.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function hasBackendUser(Request $request)
    {
        if (!$request->cookies->has('BE_USER_AUTH')) {
            return false;
        }

        $sessionHash = sha1(
            sprintf(
                '%s%sBE_USER_AUTH',
                $request->getSession()->getId(),
                $this->disableIpCheck ? '' : $request->getClientIp()
            )
        );

        return $request->cookies->get('BE_USER_AUTH') === $sessionHash;
    }
}
