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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener]
class MakeBackendResponseUncacheableListener
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly int $turboMaxAge = 3,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isBackendMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        if (
            $request->headers->has('x-turbo-request-id')
            && \in_array($request->getMethod(), [Request::METHOD_GET, Request::METHOD_HEAD], true)
        ) {
            $event->getResponse()->headers->set('Cache-Control', 'private, max-age='.$this->turboMaxAge.', must-revalidate');

            return;
        }

        $event->getResponse()->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
