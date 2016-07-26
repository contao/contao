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
use Symfony\Component\DependencyInjection\DefinitionDecorator;

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
        if (!$this->hasMigrationsBundle($container)) {
            return;
        }

        $provider = $container->getDefinition('contao.doctrine.dca_schema_provider');

        if ($this->hasOrm($container)) {
            $provider = new Definition(
                'Doctrine\DBAL\Migrations\Provider\OrmSchemaProvider',
                [$container->getDefinition('doctrine.orm.default_entity_manager')]
            );

            $container->setDefinition('contao.doctrine.schema_provider', $provider);
        }

        $command = new Definition('Doctrine\Bundle\MigrationsBundle\Command\MigrationsDiffDoctrineCommand');
        $command->setArguments([$provider]);
        $command->addTag('console.command');

        $container->setDefinition('contao.command.doctrine_migrations_diff', $command);
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
