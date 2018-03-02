<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\HeaderReplay;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
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
     * @var TokenChecker
     */
    private $tokenChecker;

    /**
     * @param ScopeMatcher $scopeMatcher
     * @param TokenChecker $tokenChecker
     */
    public function __construct(ScopeMatcher $scopeMatcher, TokenChecker $tokenChecker)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenChecker = $tokenChecker;
    }

    /**
     * Sets the "force no cache" header on the replay response to disable reverse
     * proxy caching if a user is logged in (front end preview mode).
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

        return $this->tokenChecker->hasBackendUser() || $this->tokenChecker->hasFrontendUser();
    }
}
