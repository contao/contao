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
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
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
    final public const MERGE_CACHE_HEADER = 'Contao-Merge-Cache-Control';

    private ResponseCacheStrategy|null $currentStrategy = null;

    /**
     * @var array<ResponseCacheStrategy>
     */
    private array $strategyStack = [];

    public function onKernelRequest(RequestEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
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
        $isMainRequest = HttpKernelInterface::MAIN_REQUEST === $event->getRequestType();

        if ($this->currentStrategy && $response->headers->has(self::MERGE_CACHE_HEADER)) {
            if ($isMainRequest) {
                $this->currentStrategy->update($response);
            } elseif ($response->headers->has('Cache-Control')) {
                $this->currentStrategy->add($response);
            }
        }

        if ($isMainRequest) {
            $this->currentStrategy = array_pop($this->strategyStack);
            $response->headers->remove(self::MERGE_CACHE_HEADER);
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
