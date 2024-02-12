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

use Contao\CoreBundle\DependencyInjection\Compiler\TaggedMigrationsPass;
use Contao\CoreBundle\Migration\MigrationCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TaggedMigrationsPassTest extends TestCase
{
    public function testAddsTheMigrations(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.migration.collection', new Definition(MigrationCollection::class));

        $migrations = [
            'Test\Migration1' => [],
            'Test\Migration12' => [],
            'Test\Migration2' => [],
            'Test\MigrationPrioNegative1' => ['priority' => -1],
            'Test\MigrationPrioNegative12' => ['priority' => -1],
            'Test\MigrationPrioNegative2' => ['priority' => -1],
            'Test\Migration1PrioPositive1' => ['priority' => 1],
            'Test\Migration1PrioPositive12' => ['priority' => 1],
            'Test\Migration1PrioPositive2' => ['priority' => 1],
        ];

        foreach ($migrations as $migration => $attributes) {
            $definition = new Definition($migration);
            $definition->addTag('contao.migration', $attributes);

            $container->setDefinition($migration, $definition);
        }

        $pass = new TaggedMigrationsPass();
        $pass->process($container);

        $migrationServices = $container->getDefinition('contao.migration.collection')->getArgument(0);

        $this->assertSame(
            [
                'Test\Migration1PrioPositive1',
                'Test\Migration1PrioPositive2',
                'Test\Migration1PrioPositive12',
                'Test\Migration1',
                'Test\Migration2',
                'Test\Migration12',
                'Test\MigrationPrioNegative1',
                'Test\MigrationPrioNegative2',
                'Test\MigrationPrioNegative12',
            ],
            array_keys($migrationServices),
        );
    }
}
