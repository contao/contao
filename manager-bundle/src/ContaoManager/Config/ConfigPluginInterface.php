<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Config;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface ConfigPluginInterface
{
    /**
     * Allows a plugin to prepend Container extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prependConfig(array $configs, ContainerBuilder $container);
}
