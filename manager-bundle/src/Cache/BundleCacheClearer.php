<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Cache;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class BundleCacheClearer implements CacheClearerInterface
{
    private readonly Filesystem $filesystem;

    /**
     * @internal
     */
    public function __construct(Filesystem|null $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function clear($cacheDir): void
    {
        $this->filesystem->remove(Path::join($cacheDir, 'bundles.map'));
    }
}
