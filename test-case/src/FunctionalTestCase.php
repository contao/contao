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
    protected static function loadFixtures(array $yamlFiles, bool $truncateTables = true): void
    {
        if (!self::$booted) {
            throw new \RuntimeException('Please boot the kernel before calling '.__METHOD__);
        }

        $doctrine = self::$container->get('doctrine');

        /** @var Connection $connection */
        $connection = $doctrine->getConnection();

        if ($truncateTables) {
            $platform = $connection->getDatabasePlatform();

            /** @var Table $table */
            foreach ($connection->getSchemaManager()->listTables() as $table) {
                $connection->executeStatement($platform->getTruncateTableSQL($table->getName()));
            }
        }

        // Start a transaction, otherwise each single statement will be autocommited
        $connection->beginTransaction();

        foreach ($yamlFiles as $file) {
            self::importFixture($connection, $file);
        }

        $connection->commit();
    }

    protected static function resetDatabaseSchema(): void
    {
        if (!self::$booted) {
            throw new \RuntimeException('Please boot the kernel before calling '.__METHOD__);
        }

        $doctrine = self::$container->get('doctrine');

        /** @var Connection $connection */
        $connection = $doctrine->getConnection();
        $schemaManager = $connection->getSchemaManager();
        $platform = $connection->getDatabasePlatform();

        /** @var Table $table */
        foreach ($schemaManager->listTables() as $table) {
            $connection->executeStatement($platform->getDropTableSQL($table));
        }

        /** @var EntityManagerInterface $manager */
        $manager = $doctrine->getManager();
        $metadata = $manager->getMetadataFactory()->getAllMetadata();

        $tool = new SchemaTool($manager);
        $tool->createSchema($metadata);
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
