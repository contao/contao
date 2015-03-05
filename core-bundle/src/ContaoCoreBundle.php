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
use Contao\CoreBundle\DependencyInjection\Compiler\AddContaoResourcesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\OptimizeContaoResourcesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\SetApplicationPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Configures the Contao core bundle.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCoreBundle extends Bundle
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

        $container->addCompilerPass(new SetApplicationPass());
        $container->addCompilerPass(new AddContaoResourcesPass($this->getName(), $this->getPath() . '/../contao'));

        $container->addCompilerPass(
            new OptimizeContaoResourcesPass($container->getParameter('kernel.root_dir')), PassConfig::TYPE_OPTIMIZE
        );
    }
}
