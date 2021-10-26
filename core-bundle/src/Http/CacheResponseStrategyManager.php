<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategy;
use Symfony\Contracts\Service\ResetInterface;

final class CacheResponseStrategyManager implements ResetInterface
{
    public const MERGE_CACHE_HEADER = 'Contao-Merge-Cache-Control';

    private ?ResponseCacheStrategy $currentStrategy = null;

    /**
     * @var array<ResponseCacheStrategy>
     */
    private array $strategyStack = [];

    public function init(): void
    {
        if ($this->currentStrategy) {
            $this->strategyStack[] = $this->currentStrategy;
        }

        $this->currentStrategy = new ResponseCacheStrategy();
    }

    public function add(Response $response): void
    {
        if (!$this->currentStrategy || !$response->headers->has(self::MERGE_CACHE_HEADER) || !$response->headers->has('Cache-Control')) {
            return;
        }

        $this->currentStrategy->add($response);
    }

    public function update(Response $response): void
    {
        if ($this->currentStrategy && $response->headers->has(self::MERGE_CACHE_HEADER)) {
            $this->currentStrategy->update($response);
        }

        $this->currentStrategy = array_pop($this->strategyStack);
        $response->headers->remove(self::MERGE_CACHE_HEADER);
    }

    public function reset(): void
    {
        $this->currentStrategy = null;
        $this->strategyStack = [];
    }
}
