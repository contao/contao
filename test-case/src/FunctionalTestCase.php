<?php

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
        self::bootKernel();

        $doctrine = self::$container->get('doctrine');

        /** @var Connection $connection */
        $connection = $doctrine->getConnection();

        if ($truncateTables) {
            $platform = $connection->getDatabasePlatform();

            /** @var Table $table */
            foreach ($connection->getSchemaManager()->listTables() as $table) {
                $connection->exec($platform->getTruncateTableSQL($table->getName()));
            }
        }

        foreach ($yamlFiles as $file) {
            self::importFixture($connection, $file);
        }
    }

    protected static function resetDatabaseSchema(): void
    {
        self::bootKernel();

        $doctrine = self::$container->get('doctrine');

        /** @var Connection $connection */
        $connection = $doctrine->getConnection();
        $schemaManager = $connection->getSchemaManager();
        $platform = $connection->getDatabasePlatform();

        /** @var Table $table */
        foreach ($schemaManager->listTables() as $table) {
            $connection->exec($platform->getDropTableSQL($table));
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
                    $connection->exec($row);
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
