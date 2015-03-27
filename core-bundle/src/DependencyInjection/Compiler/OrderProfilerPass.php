<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Orders the profiler templates so that Contao is the first one.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class OrderProfilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $controller = $container->getDefinition('web_profiler.controller.profiler');
        $templates  = $container->getParameter('data_collector.templates');
        $contao     = $templates['contao.data_collector'];

        unset($templates['contao.data_collector']);

        $templates = array_merge(
            ['contao.data_collector' => $contao],
            $templates
        );

        $arguments    = $controller->getArguments();
        $arguments[3] = $templates;

        $controller->setArguments($arguments);
        $container->setParameter('data_collector.templates', $templates);
    }
}
