<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\DependencyInjection;

use Contao\CoreBundle\DependencyInjection\PrependContaoExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Adds the bundle services to the container.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoManagerExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @inheritdoc
     */
    public function prepend(ContainerBuilder $container)
    {
        foreach ($container->getExtensions() as $extension) {
            if ($extension instanceof PrependContaoExtensionInterface && !$extension instanceof PrependExtensionInterface) {
                $extension->prepend($container);
            }
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
    }
}
