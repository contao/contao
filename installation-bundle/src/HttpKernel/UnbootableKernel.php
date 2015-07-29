<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\HttpKernel;

/**
 * Provides an unbootable kernel.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class UnbootableKernel extends \AppKernel
{
    /**
     * Sets the bundles array.
     *
     * @param array $bundles The bundles array
     */
    public function setBundles(array $bundles)
    {
        $this->bundles = $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        throw new \LogicException('The unbootable kernel cannot be booted.');
    }
}
