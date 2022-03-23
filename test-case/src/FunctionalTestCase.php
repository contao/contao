<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\TestCase;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Yaml;

abstract class FunctionalTestCase extends WebTestCase
{
    private static array $tableColumns = [];
    private static array $tableSchemas = [];

    protected static function loadFixtures(array $yamlFiles, bool $truncateTables = true): void
    {
        if (!self::$booted) {
            throw new \RuntimeException('Please boot the kernel before calling '.__METHOD__);
        }

        static::resetDatabaseSchema();

        $connection = self::$container->get('doctrine')->getConnection();

        foreach ($yamlFiles as $file) {
            self::importFixture($connection, $file);
        }
    }

    protected static function resetDatabaseSchema(): void
    {
        if (!self::$booted) {
            throw new \RuntimeException('Please boot the kernel before calling '.__METHOD__);
        }

        $doctrine = self::$container->get('doctrine');

        /** @var Connection $connection */
        $connection = $doctrine->getConnection();
        $connection->executeStatement('SET GLOBAL information_schema_stats_expiry = 0');

        if (!empty(self::$tableColumns)) {
            $allColumns = $connection->fetchAllNumeric('
                SELECT TABLE_NAME, COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, COLUMN_TYPE, COLLATION_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
            ');

            $tableColumns = [];

            foreach ($allColumns as $column) {
                $tableColumns[$column[0]][] = $column;
            }

            foreach (array_keys(self::$tableColumns) as $tableName) {
                if ($tableColumns[$tableName] !== self::$tableColumns[$tableName]) {
                    $connection->executeStatement('DROP TABLE '.$connection->quoteIdentifier($tableName));
                    $connection->executeStatement(self::$tableSchemas[$tableName]);
                }
            }

            $truncateTables = $connection->fetchFirstColumn('
                SELECT TABLE_NAME
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_ROWS > 0
            ');

            foreach ($truncateTables as $tableName) {
                $connection->executeStatement('TRUNCATE TABLE '.$connection->quoteIdentifier($tableName));
            }

            return;
        }

        $schemaManager = $connection->getSchemaManager();
        $tables = $schemaManager->listTables();

        if ($tables) {
            $connection->executeStatement('DROP TABLE '.implode(', ', array_map(
                static fn (Table $table) => $connection->quoteIdentifier($table->getName()),
                $tables
            )));
        }

        /** @var EntityManagerInterface $manager */
        $manager = $doctrine->getManager();
        $metadata = $manager->getMetadataFactory()->getAllMetadata();

        $tool = new SchemaTool($manager);
        $tool->createSchema($metadata);

        $tables = $schemaManager->listTables();

        $allColumns = $connection->fetchAllNumeric('
            SELECT TABLE_NAME, COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, COLUMN_TYPE, COLLATION_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
        ');

        foreach ($allColumns as $column) {
            self::$tableColumns[$column[0]][] = $column;
        }

        foreach ($tables as $table) {
            self::$tableSchemas[$table->getName()] = $connection->fetchNumeric(
                'SHOW CREATE TABLE '.$connection->quoteIdentifier($table->getName())
            )[1];
        }
    }

    private static function importFixture(Connection $connection, string $file): void
    {
        $data = Yaml::parseFile($file);

        foreach ($data as $table => $rows) {
            foreach ($rows as $row) {
                if ('sql' === $table) {
                    $connection->executeStatement($row);
                    continue;
                }

                $data = [];

                foreach ($row as $key => $value) {
                    $data[$connection->quoteIdentifier($key)] = $value;
                }

                $connection->insert($connection->quoteIdentifier($table), $data);
            }
        }
    }
}
