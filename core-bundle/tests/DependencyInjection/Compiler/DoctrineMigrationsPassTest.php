<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand;
use Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass;
use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DoctrineMigrationsPassTest extends TestCase
{
    public function testAddsTheDefinitionIfTheMigrationsBundleIsInstalled(): void
    {
        $container = $this->getContainerBuilder([DoctrineMigrationsBundle::class]);

        $pass = new DoctrineMigrationsPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(DoctrineMigrationsDiffCommand::COMMAND_ID));
        $this->assertTrue($container->getDefinition(DoctrineMigrationsDiffCommand::COMMAND_ID)->isPublic());
    }

    public function testDoesNotAddTheDefinitionIfTheMigrationsBundleIsNotInstalled(): void
    {
        $container = $this->getContainerBuilder();

        $pass = new DoctrineMigrationsPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition(DoctrineMigrationsDiffCommand::COMMAND_ID));
    }

    public function testAddsTheCommandIdToTheConsoleCommandIds(): void
    {
        $container = $this->getContainerBuilder([DoctrineMigrationsBundle::class]);

        $pass = new DoctrineMigrationsPass();
        $pass->process($container);

        $this->assertFalse($container->hasParameter('console.command.ids'));

        $container->setParameter('console.command.ids', []);

        $pass->process($container);

        $this->assertTrue($container->hasParameter('console.command.ids'));

        $this->assertContains(
            DoctrineMigrationsDiffCommand::COMMAND_ID,
            $container->getParameter('console.command.ids')
        );
    }

    /**
     * Returns a container builder that loads the commands.yml file.
     */
    private function getContainerBuilder(array $bundles = []): ContainerBuilder
    {
        $definition = new Definition(DcaSchemaProvider::class);
        $definition->addArgument('foo');

        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $bundles);
        $container->setDefinition('service_container', (new Definition(Container::class, []))->setSynthetic(true));
        $container->setDefinition('contao.doctrine.schema_provider', $definition);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../../src/Resources/config'));
        $loader->load('commands.yml');

        return $container;
    }
}
