<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Sets the application name and version in the web profiler.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class SetApplicationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('data_collector.config')) {
            return;
        }

        $definition = $container->findDefinition('data_collector.config');

        $definition->addArgument('Contao');
        $definition->addArgument('4.0.0'); // FIXME: output the dist version
    }
}
