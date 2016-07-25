<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DoctrineMigrationsPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $provider = new Definition(
            'Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider',
            [
                $container->getDefinition('contao.framework'),
                $container->getDefinition('doctrine.dbal.default_connection')
            ]
        );

        if ($this->hasOrm($container)) {
            $dca = $provider;
            $orm = new Definition(
                'Doctrine\DBAL\Migrations\Provider\OrmSchemaProvider',
                [$container->getDefinition('doctrine.orm.default_entity_manager')]
            );

            $provider = new Definition('Contao\CoreBundle\Doctrine\Schema\CompositeSchemaProvider');
            $provider->addMethodCall('add', [$orm]);
            $provider->addMethodCall('add', [$dca]);
        }

        $definitions = [
            'contao.migrations.schema_provider' => $provider,
        ];

        if ($this->hasMigrationsBundle($container)) {
            $command = new Definition('Doctrine\Bundle\MigrationsBundle\Command\MigrationsDiffDoctrineCommand');
            $command->setArguments([$provider]);
            $command->addTag('console.command');

            $definitions['contao.migrations.diff_command'] = $command;
        }

        $container->addDefinitions($definitions);
    }

    /**
     * Check if doctrine migrations bundle is enabled.
     *
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private function hasMigrationsBundle(ContainerBuilder $container)
    {
        return in_array(
            'Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle',
            $container->getParameter('kernel.bundles'),
            true
        );
    }

    /**
     * Checks if Doctrine ORM is enabled.
     *
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private function hasOrm(ContainerBuilder $container)
    {
        return $container->has('doctrine.orm.entity_manager');
    }
}
