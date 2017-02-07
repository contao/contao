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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        $this->filesystem->remove($cacheDir.'/bundles.map');
    }
}
