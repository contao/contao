<?php

declare(strict_types=1);

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
     * @param ScopeMatcher $scopeMatcher
     * @param bool         $disableIpCheck
     */
    public function __construct(ScopeMatcher $scopeMatcher, bool $disableIpCheck)
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
    public function onReplay(HeaderReplayEvent $event): void
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
    private function hasContaoUser(Request $request): bool
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
     * @throws \RuntimeException
     *
     * @return bool
     */
    private function hasFrontendUser(Request $request): bool
    {
        if (!$request->cookies->has('FE_USER_AUTH')) {
            return false;
        }

        $session = $request->getSession();

        if (null === $session) {
            throw new \RuntimeException('The request did not contain a session object');
        }

        $sessionHash = sha1(
            sprintf(
                '%s%sFE_USER_AUTH',
                $session->getId(),
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
     * @throws \RuntimeException
     *
     * @return bool
     */
    private function hasBackendUser(Request $request): bool
    {
        if (!$request->cookies->has('BE_USER_AUTH')) {
            return false;
        }

        $session = $request->getSession();

        if (null === $session) {
            throw new \RuntimeException('The request did not contain a session object');
        }

        $sessionHash = sha1(
            sprintf(
                '%s%sBE_USER_AUTH',
                $session->getId(),
                $this->disableIpCheck ? '' : $request->getClientIp()
            )
        );

        return $request->cookies->get('BE_USER_AUTH') === $sessionHash;
    }
}
