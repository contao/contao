<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ContaoInstallationExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        static $files = [
            'commands.yml',
            'listener.yml',
            'services.yml',
        ];

        foreach ($files as $file) {
            $loader->load($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $configDir = $container->getParameter('kernel.project_dir').'/app/config';

        if (file_exists($configDir.'/parameters.yml') || !file_exists($configDir.'/parameters.yml.dist')) {
            return;
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator($configDir)
        );

        $loader->load('parameters.yml.dist');
    }
}
