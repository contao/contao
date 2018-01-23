<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

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

    /**
     * @param TokenChecker $tokenChecker
     * @param string       $requestAttribute
     */
    public function __construct(TokenChecker $tokenChecker, string $requestAttribute = '_bypass_maintenance')
    {
        $this->tokenChecker = $tokenChecker;
        $this->requestAttribute = $requestAttribute;
    }

    /**
     * Adds the request attribute to the request.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (!$this->tokenChecker->hasBackendUser()) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set($this->requestAttribute, true);
    }
}
