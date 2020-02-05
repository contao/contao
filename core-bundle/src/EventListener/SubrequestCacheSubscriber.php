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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\ResetInterface;

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
class SubrequestCacheSubscriber implements EventSubscriberInterface, ResetInterface
{
    public const MERGE_CACHE_HEADER = 'Contao-Merge-Cache-Control';

    /**
     * @var array<ResponseCacheStrategy>
     */
    private $strategyStack = [];

    /**
     * @var ResponseCacheStrategy|null
     */
    private $currentStrategy;

    public function onKernelRequest(RequestEvent $event): void
    {
        if (KernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if ($this->currentStrategy) {
            $this->strategyStack[] = $this->currentStrategy;
        }

        $this->currentStrategy = new ResponseCacheStrategy();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $isMasterRequest = KernelInterface::MASTER_REQUEST === $event->getRequestType();

        if ($this->currentStrategy && $response->headers->has(self::MERGE_CACHE_HEADER)) {
            if ($isMasterRequest) {
                $this->currentStrategy->update($response);
            } elseif ($response->headers->has('Cache-Control')) {
                $this->currentStrategy->add($response);
            }
        }

        if ($isMasterRequest) {
            $this->currentStrategy = array_pop($this->strategyStack);
        }
    }

    public function reset(): void
    {
        $this->currentStrategy = null;
        $this->strategyStack = [];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
            KernelEvents::RESPONSE => ['onKernelResponse', -255],
        ];
    }
}
