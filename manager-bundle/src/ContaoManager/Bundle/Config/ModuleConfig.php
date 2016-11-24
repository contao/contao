<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Bundle\Config;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ModuleConfig extends BundleConfig
{
    /**
     * @inheritdoc
     *
     * @throws \LogicException
     */
    public function getBundleInstance(KernelInterface $kernel)
    {
        return new ContaoModuleBundle($this->name, $kernel->getRootDir());
    }
}
