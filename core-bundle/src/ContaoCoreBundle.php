<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle;

use Contao\System;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures the Contao core bundle.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoCoreBundle extends ContaoBundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        System::boot();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        System::boot();
    }
}
