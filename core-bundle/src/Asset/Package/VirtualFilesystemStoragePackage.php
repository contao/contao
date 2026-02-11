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

use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Symfony\Component\Asset\PackageInterface;

class VirtualFilesystemStoragePackage implements PackageInterface
{
    public function __construct(private readonly VirtualFilesystem $storage)
    {
    }

    public function getVersion(string $path): string
    {
        // TODO: Once we have native checksum support (see #7630), we could use that for
        // the version hashes on and/or to directly generate the public URIs including
        // the checksum/version hash.
        return hash('xxh3', (string) $this->storage->getLastModified($path));
    }

    public function getUrl(string $path): string
    {
        return $this->storage
            ->generatePublicUri($path)
            ?->withQuery('v='.$this->getVersion($path))
            ->__toString() ?? $path
        ;
    }
}
