<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\HttpKernel\ContaoKernelInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Writes the bundles.map file in the cache directory.
 *
 * @author Leo Feyer <https://contao.org>
 */
class AddBundlesToCachePass implements CompilerPassInterface
{
    /**
     * @var ContaoKernelInterface
     */
    private $kernel;

    /**
     * Constructor.
     *
     * @param ContaoKernelInterface $kernel The kernel object
     */
    public function __construct(ContaoKernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->kernel->writeBundleCache();
    }
}
