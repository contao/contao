<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Cache;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * Clears the bundle cache.
 *
 * @author Kamil Kuzminski <https://github.com/qzminski>
 */
class BundleCacheClearer implements CacheClearerInterface
{
    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        $filesystem = new Filesystem();
        $filesystem->remove($cacheDir.'/bundles.map');
    }
}
