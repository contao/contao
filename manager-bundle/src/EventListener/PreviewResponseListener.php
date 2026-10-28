<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\EventListener;

use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -4096)]
class PreviewResponseListener
{
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getRequest()->attributes->get('_preview')) {
            return;
        }

        $response = $event->getResponse();

        // Prevent preview URLs from being indexed
        $response->headers->set('X-Robots-Tag', 'noindex');

        // Force no-cache on all responses in the preview front controller
        $response->headers->set('Cache-Control', 'no-store');

        // Strip all tag headers from the response
        $response->headers->remove(TagHeaderFormatter::DEFAULT_HEADER_NAME);
    }
}
