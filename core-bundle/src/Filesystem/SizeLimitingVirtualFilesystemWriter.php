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
    public function __construct(private readonly VirtualFilesystemInterface $virtualFilesystem)
    {
    }

    /**
     * @param resource $contents
     */
    public function writeStream(Uuid|string $location, $contents, int $maximumSize): void
    {
        FilesystemUtil::assertIsResource($contents);
        $state = new LimitStreamSizeFilterState($maximumSize);
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
    private function appendFilter($contents, LimitStreamSizeFilterState $state)
    {
        if (false === ($filter = stream_filter_append($contents, LimitStreamSizeFilter::NAME, STREAM_FILTER_READ, $state))) {
            throw new \RuntimeException('Could not apply the maximum stream size.');
        }

        return $filter;
    }
}
