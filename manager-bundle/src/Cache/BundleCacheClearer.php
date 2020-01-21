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
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class BundleCacheClearer implements CacheClearerInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @internal Do not inherit from this class; decorate the "contao_manager.cache.clear_bundle" service instead
     */
    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function clear($cacheDir): void
    {
        $this->filesystem->remove($cacheDir.'/bundles.map');
    }
}
