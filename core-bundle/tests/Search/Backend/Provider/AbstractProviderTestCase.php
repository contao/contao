<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\Provider;

use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Tools\DsnParser;

abstract class AbstractProviderTestCase extends TestCase
{
    /**
     * @param array<Table>                                    $tables
     * @param array<string, array<int, array<string, mixed>>> $inserts
     */
    protected function createInMemorySQLiteConnection(array $tables, array $inserts): Connection
    {
        $dsnParser = new DsnParser();
        $connectionParams = $dsnParser->parse('pdo-sqlite:///:memory:');

        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        try {
            $connection = DriverManager::getConnection($connectionParams, $configuration);

            foreach ($tables as $table) {
                $connection->createSchemaManager()->createTable($table);
            }
        } catch (\Exception) {
            $this->markTestSkipped('This test requires SQLite to be executed properly.');
        }

        foreach ($inserts as $table => $rows) {
            foreach ($rows as $row) {
                $connection->insert($table, $row);
            }
        }

        return $connection;
    }
}
