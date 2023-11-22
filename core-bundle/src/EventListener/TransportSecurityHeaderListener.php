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
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @internal
 */
class TransportSecurityHeaderListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly int $ttl,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isContaoMainRequest($event) || !$event->getRequest()->isSecure()) {
            return;
        }

        $event->getResponse()->headers->set('Strict-Transport-Security', 'max-age='.$this->ttl);
    }
}
