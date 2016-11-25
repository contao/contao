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
    const DIFF_COMMAND_ID = 'console.command.contao_corebundle_command_doctrinemigrationsdiffcommand';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$this->hasMigrationsBundle($container)) {
            return;
        }

        if ($this->hasOrm($container)) {
            // Use Doctrine mapping (enhanced by our listeners) for schema if ORM is installed
            $provider = new Definition(
                'Doctrine\DBAL\Migrations\Provider\OrmSchemaProvider',
                [$container->findDefinition('doctrine.orm.entity_manager')]
            );
        } else {
            // Migrations schema provider must implement interface (only available if bundle is installed)
            $provider = new DefinitionDecorator('contao.doctrine.dca_schema_provider');
            $provider->setClass('Contao\CoreBundle\Doctrine\Schema\MigrationsSchemaProvider');

            $this->registerDiffCommand($container, $provider);
        }

        $container->setDefinition('contao.doctrine.schema_provider', $provider);
    }

    /**
     * Checks if the Doctrine migrations bundle is enabled.
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

    /**
     * Registers the custom doctrine:schema:diff command that works without ORM.
     *
     * @param ContainerBuilder $container
     * @param Definition       $provider
     */
    private function registerDiffCommand(ContainerBuilder $container, Definition $provider)
    {
        $command = new Definition('Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand');
        $command->setArguments([$provider]);
        $command->addTag('console.command');

        $container->setDefinition(static::DIFF_COMMAND_ID, $command);

        // Required if Symfony's compiler pass has already handled the "console.command" tags
        if ($container->hasParameter('console.command.ids')) {
            $ids = $container->getParameter('console.command.ids');
            $ids[] = static::DIFF_COMMAND_ID;

            $container->setParameter('console.command.ids', $ids);
        }
    }
}
