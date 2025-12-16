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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener]
class BackendCacheResponseListener
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
        $response = $event->getResponse();

        // Requests with "Accept: text/vnd.turbo-stream.html, text/html" might return a
        // different response body than "Accept: text/html" (#9128)
        $response->headers->set('Vary', 'Accept');

        if ($request->headers->has('x-turbo-request-id') && $request->isMethodCacheable() && Response::HTTP_OK === $response->getStatusCode()) {
            $response->headers->set('Cache-Control', 'private, max-age='.$this->turboMaxAge.', must-revalidate');

            return;
        }

        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
