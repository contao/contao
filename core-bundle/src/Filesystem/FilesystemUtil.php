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

/**
 * @internal
 */
class FilesystemUtil
{
    /**
     * @param mixed $contents
     *
     * @see \League\Flysystem\Filesystem::assertIsResource()
     */
    public static function assertIsResource($contents): void
    {
        if (false === \is_resource($contents)) {
            $type = \gettype($contents);

            throw new \LogicException(sprintf('Invalid stream provided, expected stream resource, received "%s".', $type));
        }

        if ('stream' !== ($type = get_resource_type($contents))) {
            throw new \LogicException(sprintf('Invalid stream provided, expected stream resource, received resource of type "%s".', $type));
        }
    }

    /**
     * @param resource $resource
     *
     * @see \League\Flysystem\Filesystem::rewindStream()
     */
    public static function rewindStream($resource): void
    {
        if (0 !== ftell($resource) && stream_get_meta_data($resource)['seekable']) {
            rewind($resource);
        }
    }
}
