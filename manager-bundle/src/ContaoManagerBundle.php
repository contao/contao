<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle;

use Contao\ManagerBundle\DependencyInjection\Compiler\ContaoManagerPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ContaoManagerBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ContaoManagerPass());
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/controller.yaml');
        $configurator->import('../config/services.yaml');

        $container->setParameter('contao_manager.manager_path', $config['manager_path']);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
                ->children()
                    ->scalarNode('manager_path')
                    ->defaultNull()
                    ->info('The path to the Contao manager relative to the Contao web directory.')
                ->end()
            ->end()
        ;
    }
}
