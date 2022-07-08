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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @internal
 *
 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
 */
class LegacyLoginConstantsListener
{
    /**
     * @var TokenChecker
     */
    private $tokenChecker;

    /** 
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    public function __construct(TokenChecker $tokenChecker, ScopeMatcher $scopeMatcher)
    {
        $this->tokenChecker = $tokenChecker;
        $this->scopeMatcher = $scopeMatcher;
    }

    public function __invoke(RequestEvent $event): void
    {
        if ($this->scopeMatcher->isFrontendRequest($event->getRequest())) {
            \define('BE_USER_LOGGED_IN', $this->tokenChecker->hasBackendUser() && $this->tokenChecker->isPreviewMode());
            \define('FE_USER_LOGGED_IN', $this->tokenChecker->hasFrontendUser());
        } else {
            \define('BE_USER_LOGGED_IN', false);
            \define('FE_USER_LOGGED_IN', false);   
        }
    }
}
