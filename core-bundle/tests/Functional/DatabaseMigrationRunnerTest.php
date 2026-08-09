<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\CoreBundle\Migration\DatabaseMigrationRunner;
use Contao\System;
use Contao\TestCase\FunctionalTestCase;
use Doctrine\DBAL\Connection;

class DatabaseMigrationRunnerTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        self::resetDatabaseSchema();

        parent::tearDown();
    }

    public function testMigratesTheEntireDatabase(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        System::setContainer($container);

        self::resetDatabaseSchema();

        /** @var Connection $connection */
        $connection = $container->get('doctrine')->getConnection();
        $connection->executeStatement('ALTER TABLE tl_content DROP text');

        /** @var DatabaseMigrationRunner $runner */
        $runner = $container->get('contao.migration.database_runner');
        $runner->runAll();

        $columns = $connection->createSchemaManager()->listTableColumns('tl_content');

        $this->assertArrayHasKey('text', $columns);
    }
}
