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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @internal
 */
#[AsEventListener]
class BackendNoindexListener
{
    public function __construct(private readonly ScopeMatcher $scopeMatcher)
    {
    }

    /**
     * Adds "X-Robots-Tag: noindex" to the response for the back end.
     */
    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isBackendMainRequest($event)) {
            return;
        }

        $event->getResponse()->headers->set('X-Robots-Tag', 'noindex');
    }
}
