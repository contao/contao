<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * Adds the bundle services to the container.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class ContaoCoreExtension extends ConfigurableExtension
{
    /**
     * @var array
     */
    private $files = [
        'adapter.yml',
        'cache.yml',
        'commands.yml',
        'listener.yml',
        'services.yml',
    ];

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'contao';
    }

    /**
     * {@inheritdoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        foreach ($this->files as $file) {
            $loader->load($file);
        }

        $container->setParameter('contao.prepend_locale', $mergedConfig['prepend_locale']);
        $container->setParameter('contao.encryption_key', $mergedConfig['encryption_key']);
        $container->setParameter('contao.url_suffix', $mergedConfig['url_suffix']);
        $container->setParameter('contao.upload_path', $mergedConfig['upload_path']);
        $container->setParameter('contao.csrf_token_name', $mergedConfig['csrf_token_name']);
        $container->setParameter('contao.pretty_error_screens', $mergedConfig['pretty_error_screens']);
        $container->setParameter('contao.error_level', $mergedConfig['error_level']);
    }
}
