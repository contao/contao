<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;
use Symfony\Contracts\Service\ResetInterface;

final class FragmentResponseStack implements ResetInterface
{
    public const MERGE_CACHE_HEADER = 'Contao-Merge-Cache-Control';

    private ?FragmentResponseCollection $currentCollection = null;

    /**
     * @var array<FragmentResponseCollection>
     */
    private array $collectionStack = [];

    /**
     * Initializes and pushes new fragment response collection for a new main request.
     */
    public function init(): void
    {
        if ($this->currentCollection) {
            $this->collectionStack[] = $this->currentCollection;
        }

        $this->currentCollection = new FragmentResponseCollection();
    }

    /**
     * Adds a fragment response to the current collection.
     */
    public function add(Response $response): void
    {
        if (!$this->currentCollection) {
            return;
        }

        $this->currentCollection->add($response);
    }

    /**
     * Finalizes the current fragment response collection on the given response.
     */
    public function finalize(Response $response): void
    {
        $this->mergeCurrentCacheHeaders($response);

        $this->currentCollection = array_pop($this->collectionStack);
    }

    public function reset(): void
    {
        $this->currentCollection = null;
        $this->collectionStack = [];
    }

    /**
     * Merges the cache headers for the current fragment response collection.
     */
    private function mergeCurrentCacheHeaders(Response $response): void
    {
        if (!$this->currentCollection) {
            return;
        }

        $cacheStrategy = new ResponseCacheStrategy();

        foreach ($this->currentCollection->get() as $fragmentResponse) {
            if ($response->headers->has(self::MERGE_CACHE_HEADER) && $response->headers->has('Cache-Control')) {
                $cacheStrategy->add($fragmentResponse);
            }
        }

        $cacheStrategy->update($response);
        $response->headers->remove(self::MERGE_CACHE_HEADER);
    }
}
