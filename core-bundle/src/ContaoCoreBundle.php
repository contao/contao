<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle;

use Contao\CoreBundle\DependencyInjection\Compiler\SetApplicationPass;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
    public function boot()
    {
        require_once __DIR__ . '/../contao/bootstrap.php';

        // TODO: should the scopes be defined as constant like ContainerInterface::SCOPE_CONTAINER?
        $this->container->addScope(new Scope('frontend', 'request'));
        $this->container->addScope(new Scope('backend', 'request'));
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        require_once __DIR__ . '/../contao/bootstrap.php';

        $container->addCompilerPass(new SetApplicationPass());
    }
}
