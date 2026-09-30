<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

use Symfony\Component\Uid\Uuid;

/**
 * @experimental
 */
final class SizeLimitingVirtualFilesystemWriter
{
    private static bool $filterRegistered = false;

    public function __construct(private readonly VirtualFilesystemInterface $virtualFilesystem)
    {
    }

    /**
     * @param resource $contents
     */
    public function writeStream(Uuid|string $location, $contents, int $maximumSize): void
    {
        FilesystemUtil::assertIsResource($contents);
        $state = new MaximumStreamSizeFilterState($maximumSize);
        $filter = $this->appendFilter($contents, $state);

        try {
            try {
                $this->virtualFilesystem->writeStream($location, $contents);
            } catch (\Throwable $exception) {
                if ($state->isExceeded()) {
                    throw new MaximumStreamSizeExceededException($maximumSize, $exception);
                }

                throw $exception;
            }

            if ($state->isExceeded()) {
                throw new MaximumStreamSizeExceededException($maximumSize);
            }
        } finally {
            if (\is_resource($filter)) {
                stream_filter_remove($filter);
            }
        }
    }

    /**
     * @param resource $contents
     *
     * @return resource
     */
    private function appendFilter($contents, MaximumStreamSizeFilterState $state)
    {
        if (!self::$filterRegistered) {
            self::$filterRegistered = stream_filter_register(MaximumStreamSizeFilter::NAME, MaximumStreamSizeFilter::class);
        }

        if (!self::$filterRegistered || false === ($filter = stream_filter_append($contents, MaximumStreamSizeFilter::NAME, STREAM_FILTER_READ, $state))) {
            throw new \RuntimeException('Could not apply the maximum stream size.');
        }

        return $filter;
    }
}
