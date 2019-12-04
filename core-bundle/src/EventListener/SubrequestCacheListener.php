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

        if ($this->currentStrategy && $response->headers->has(self::MERGE_CACHE_HEADER)) {
            if (KernelInterface::MASTER_REQUEST === $event->getRequestType()) {
                $this->currentStrategy->update($response);
            } else {
                $this->currentStrategy->add($response);
            }
        }

        if (KernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->currentStrategy = array_pop($this->strategyStack);
        }
    }
}
