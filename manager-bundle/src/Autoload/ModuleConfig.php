<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Autoload;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ModuleConfig extends Config
{
    /**
     * @inheritdoc
     */
    public function getBundleInstance(KernelInterface $kernel)
    {
        try {
            return new ContaoModuleBundle($this->name, $kernel->getRootDir());
        } catch (\LogicException $e) {
            return null;
        }
    }
}
