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
    protected function loadFixture(string $yamlFile, bool $resetDatabase = true): void
    {
        self::bootKernel();

        $doctrine = self::$container->get('doctrine');

        /** @var Connection $connection */
        $connection = $doctrine->getConnection();

        if ($resetDatabase) {
            $this->resetDatabase();
        }

        $data = Yaml::parseFile($yamlFile);

        foreach ($data as $table => $rows) {
            foreach ($rows as $row) {
                if ('sql' === $table) {
                    $connection->exec($row);
                    continue;
                }

                $connection->insert($table, $row);
            }
        }
    }

    private function resetDatabase(): void
    {
        $doctrine = self::$container->get('doctrine');

        /** @var Connection $connection */
        $connection = $doctrine->getConnection();
        $schemaManager = $connection->getSchemaManager();

        /** @var Table $table */
        foreach ($schemaManager->listTables() as $table) {
            $connection->exec('DROP TABLE '.$table->getName());
        }

        /** @var EntityManagerInterface $manager */
        $manager = $doctrine->getManager();
        $metadata = $manager->getMetadataFactory()->getAllMetadata();

        $tool = new SchemaTool($manager);
        $tool->createSchema($metadata);
    }
}
