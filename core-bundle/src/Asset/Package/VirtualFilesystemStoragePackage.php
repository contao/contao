<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Asset\Package;

use Contao\CoreBundle\Filesystem\PublicUri\AbstractPublicUriProvider;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Symfony\Component\Asset\PackageInterface;

class VirtualFilesystemStoragePackage implements PackageInterface
{
    public function __construct(private readonly VirtualFilesystem $storage)
    {
    }

    public function getVersion(string $path): string
    {
        if ($uri = $this->storage->generatePublicUri($path)) {
            parse_str($uri->getQuery(), $params);

            $version = $params[AbstractPublicUriProvider::VERSION_QUERY_PARAMETER] ?? null;
            if (null !== $version && '' !== $version) {
                return (string) $version;
            }

            // Fallback if no version could be found
            return hash('xxh3', (string) $this->storage->getLastModified($path));
        }

        return '';
    }

    public function getUrl(string $path): string
    {
        if ($uri = $this->storage->generatePublicUri($path)) {
            return (string) $uri;
        }

        return $path;
    }
}
