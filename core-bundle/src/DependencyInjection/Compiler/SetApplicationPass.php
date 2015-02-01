<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Set application name and version for web profiler.
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
        $definition = $container->findDefinition('data_collector.config');

        $definition->addArgument('Contao');
        $definition->addArgument(VERSION . '.' . BUILD);
    }
}
