<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand;
use Contao\CoreBundle\Doctrine\Schema\MigrationsSchemaProvider;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineMigrationsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$this->hasMigrationsBundle($container)) {
            return;
        }

        $provider = new Definition(MigrationsSchemaProvider::class);
        $provider->addArgument(new Reference('contao.framework'));
        $provider->addArgument(new Reference('doctrine'));

        $command = new Definition(DoctrineMigrationsDiffCommand::class);
        $command->setArguments([$provider]);
        $command->addTag('console.command');
        $command->setPublic(true);

        $container->setDefinition(DoctrineMigrationsDiffCommand::COMMAND_ID, $command);

        // Required if Symfony's compiler pass has already handled the "console.command" tags
        if ($container->hasParameter('console.command.ids')) {
            $ids = $container->getParameter('console.command.ids');
            $ids[] = DoctrineMigrationsDiffCommand::COMMAND_ID;

            $container->setParameter('console.command.ids', $ids);
        }
    }

    private function hasMigrationsBundle(ContainerBuilder $container): bool
    {
        return \in_array(DoctrineMigrationsBundle::class, $container->getParameter('kernel.bundles'), true);
    }
}
