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

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @internal
 */
class BypassMaintenanceListener
{
    /**
     * @var TokenChecker
     */
    private $tokenChecker;

    /**
     * @var string
     */
    private $requestAttribute;

    public function __construct(TokenChecker $tokenChecker, string $requestAttribute = '_bypass_maintenance')
    {
        $this->tokenChecker = $tokenChecker;
        $this->requestAttribute = $requestAttribute;
    }

    /**
     * Adds the request attribute to the request.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->tokenChecker->hasBackendUser()) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set($this->requestAttribute, true);
    }
}
