<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class ContaoManagerExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('contao.web_dir'));
    }

    /**
     * {@inheritdoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('commands.yml');
        $loader->load('listener.yml');
        $loader->load('services.yml');

        $this->configureManagerUrlParameter($mergedConfig, $container);
    }

    /**
     * Configure the manager url porameter by checking configuration or default paths.
     */
    protected function configureManagerUrlParameter(array $mergedConfig, ContainerBuilder $container): void
    {
        $managerPath = null;

        if ($mergedConfig['manager_path']) {
            $managerPath = $mergedConfig['manager_path'];
        } else {
            $webDir = $container->getParameter('contao.web_dir');

            if (is_file($webDir . '/contao-manager.phar.php')) {
                $managerPath = 'contao-manager.phar.php';
            }
        }

        $container->setParameter('contao_manager.manager_path', $managerPath);
    }
}
