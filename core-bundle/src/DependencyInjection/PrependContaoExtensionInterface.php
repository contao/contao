<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// TODO: must go to core bundle
namespace Contao\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Defines the interface required for Contao bundles to prepend Container configuration.
 *
 * This is a clone of Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface
 * to allow to prepend the configuration of bundles only if contao/manager-bundle is installed.
 */
interface PrependContaoExtensionInterface
{
    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container);
}
