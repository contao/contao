<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\DependencyInjection;

use Contao\ManagerBundle\ContaoManager\Config\ConfigPluginInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Dummy Plugin.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class TestPlugin implements ConfigPluginInterface
{
    /**
     * Allows a plugin to prepend Container extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prependConfig(array $configs, ContainerBuilder $container)
    {
        $container->setDefinition('foo', new Definition('barClass'));
    }
}
