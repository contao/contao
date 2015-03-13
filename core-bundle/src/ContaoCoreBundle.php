<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle;

use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundle;
use Symfony\Component\DependencyInjection\Scope;

/**
 * Configures the Contao core bundle.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCoreBundle extends ContaoBundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new ContaoCoreExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->container->addScope(new Scope('frontend', 'request'));
        $this->container->addScope(new Scope('backend', 'request'));
    }
}
