<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass;
use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DoctrineMigrationsPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pass = new DoctrineMigrationsPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass', $pass);
    }

    public function testAddsTheDefinitionIfTheMigrationsBundleIsInstalled(): void
    {
        $container = $this->getContainerBuilder([DoctrineMigrationsBundle::class]);

        $pass = new DoctrineMigrationsPass();
        $pass->process($container);

        $this->assertTrue(
            $container->hasDefinition('console.command.contao_corebundle_command_doctrinemigrationsdiffcommand')
        );
    }

    public function testDoesNotAddTheDefinitionIfTheMigrationsBundleIsNotInstalled(): void
    {
        $container = $this->getContainerBuilder();

        $pass = new DoctrineMigrationsPass();
        $pass->process($container);

        $this->assertFalse(
            $container->hasDefinition('console.command.contao_corebundle_command_doctrinemigrationsdiffcommand')
        );
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
            'console.command.contao_corebundle_command_doctrinemigrationsdiffcommand',
            $container->getParameter('console.command.ids')
        );
    }

    /**
     * Returns a container builder that loads the commands.yml file.
     *
     * @param array $bundles
     *
     * @return ContainerBuilder
     */
    private function getContainerBuilder(array $bundles = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $bundles);
        $container->setDefinition('service_container', (new Definition(Container::class, []))->setSynthetic(true));

        $container->setDefinition(
            'contao.doctrine.schema_provider',
            (new Definition(DcaSchemaProvider::class))->addArgument('foo')
        );

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../../src/Resources/config')
        );

        $loader->load('commands.yml');

        return $container;
    }
}
