<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cache;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class ContaoCacheClearer implements CacheClearerInterface
{
    /**
     * @internal Do not inherit from this class; decorate the "contao.cache.clearer" service instead
     */
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function clear(string $cacheDir): void
    {
        $this->filesystem->remove(Path::join($cacheDir, 'contao/config'));
        $this->filesystem->remove(Path::join($cacheDir, 'contao/dca'));
        $this->filesystem->remove(Path::join($cacheDir, 'contao/languages'));
        $this->filesystem->remove(Path::join($cacheDir, 'contao/sql'));
    }
}
