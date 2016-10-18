<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests the DoctrineMigrationsPass class.
 *
 * @author Andreas Schempp <http://github.com/aschempp>
 */
class DoctrineMigrationsPassTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pass = new DoctrineMigrationsPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass', $pass);
    }

    public function testWithoutMigrationsBundle()
    {
        $pass = new DoctrineMigrationsPass();
        $container = new ContainerBuilder();

        $container->setParameter('kernel.bundles', []);

        $pass->process($container);

        $this->assertFalse($container->hasDefinition('contao.doctrine.schema_provider'));
        $this->assertFalse($container->hasDefinition('contao.command.doctrine_migrations_diff'));
    }

    public function testWithOrm()
    {
        $pass = new DoctrineMigrationsPass();
        $container = new ContainerBuilder();

        $container->setParameter('kernel.bundles', ['Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle']);
        $container->setDefinition('doctrine.orm.entity_manager', new Definition());

        $pass->process($container);

        $this->assertTrue($container->hasDefinition('contao.doctrine.schema_provider'));

        $def = $container->getDefinition('contao.doctrine.schema_provider');
        $this->assertEquals('Doctrine\DBAL\Migrations\Provider\OrmSchemaProvider', $def->getClass());
    }

    public function testWithoutOrm()
    {
        $pass = new DoctrineMigrationsPass();
        $container = new ContainerBuilder();

        $container->setParameter('kernel.bundles', ['Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle']);

        $pass->process($container);

        $this->assertTrue($container->hasDefinition('contao.doctrine.schema_provider'));
        $this->assertEquals(
            'Contao\CoreBundle\Doctrine\Schema\MigrationsSchemaProvider',
            $container->getDefinition('contao.doctrine.schema_provider')->getClass()
        );

        $this->assertTrue($container->hasDefinition('contao.command.doctrine_migrations_diff'));
        $this->assertEquals(
            'Contao\CoreBundle\Command\DoctrineMigrationsDiffCommand',
            $container->getDefinition('contao.command.doctrine_migrations_diff')->getClass()
        );
    }

    public function testAddsCommandId()
    {
        $pass = new DoctrineMigrationsPass();
        $container = new ContainerBuilder();

        $container->setParameter('kernel.bundles', ['Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle']);

        $pass->process($container);

        $this->assertFalse($container->hasParameter('console.command.ids'));

        $container->setParameter('console.command.ids', []);

        $pass->process($container);

        $this->assertTrue($container->hasParameter('console.command.ids'));
        $this->assertContains(
            'contao.command.doctrine_migrations_diff',
            $container->getParameter('console.command.ids')
        );
    }
}
