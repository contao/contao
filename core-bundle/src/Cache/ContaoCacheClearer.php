<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Cache;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * Removes the Contao cache directory during cache clear.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCacheClearer implements CacheClearerInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param Filesystem $filesystem The filesystem object
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
        $this->filesystem->remove("$cacheDir/contao/config");
        $this->filesystem->remove("$cacheDir/contao/dca");
        $this->filesystem->remove("$cacheDir/contao/languages");
        $this->filesystem->remove("$cacheDir/contao/sql");
    }
}
