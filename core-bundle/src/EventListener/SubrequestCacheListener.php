<?php

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;
use Symfony\Component\HttpKernel\KernelInterface;

class SubrequestCacheListener
{
    public const MERGE_CACHE_HEADER = 'Sf-Merge-Cache-Control';

    /**
     * @var ResponseCacheStrategy[]
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

        if (KernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            if ($this->currentStrategy && $response->headers->has(self::MERGE_CACHE_HEADER)) {
                $this->currentStrategy->update($response);
            }

            $this->currentStrategy = array_pop($this->strategyStack);

            return;
        }

        if ($this->currentStrategy && $response->headers->has('Cache-Control')) {
            $this->currentStrategy->add($response);
        }
    }
}
