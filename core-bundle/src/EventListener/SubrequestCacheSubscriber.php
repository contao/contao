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

use Contao\CoreBundle\Http\CacheResponseStrategyManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The Symfony HttpCache ships with a ResponseCacheStrategy, which is used to
 * merge the caching information of multiple ESI subrequests with the main
 * response. It will make sure that the final response has the lowest possible
 * cache time.
 *
 * In Contao, we use the same cache strategy to merge inline fragments into the
 * main page content. This means a fragment like a content element or frontend
 * module can influence the cache time of the page. A user might configure a
 * cache time of 1 day in the page settings, but the news list module might
 * know there is a news item scheduled for publishing in 5 hours (start time),
 * so the page cache time will be set to 5 hours instead.
 *
 * To apply the cache merging, a specific header needs to be present in both
 * the main and subrequest response. The header is automatically set for the
 * page content and classes implementing the abstract content element and
 * module controllers.
 *
 * @internal
 */
class SubrequestCacheSubscriber implements EventSubscriberInterface
{
    public const MERGE_CACHE_HEADER = CacheResponseStrategyManager::MERGE_CACHE_HEADER;

    private CacheResponseStrategyManager $strategy;

    public function __construct(CacheResponseStrategyManager $strategy)
    {
        $this->strategy = $strategy;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->strategy->init();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            $this->strategy->add($event->getResponse());
        } else {
            $this->strategy->update($event->getResponse());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
            KernelEvents::RESPONSE => ['onKernelResponse', -255],
        ];
    }
}
